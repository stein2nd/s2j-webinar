<?php

/**
 * Execute service request materials with plugin-side Authorization.
 *
 * @package S2J_Webinar
 */

declare(strict_types=1);

namespace S2J\Webinar\Http;

use S2J\Webinar\Constants;
use S2J\Webinar\OAuth\TokenStore;

use function S2J\WebinarService\build_oauth_refresh_request;
use function S2J\WebinarService\build_oauth_token_exchange_request;
use function base64_encode;
use function http_build_query;
use function json_decode;
use function ltrim;
use function rtrim;
use function str_starts_with;
use function strtoupper;
use function time;

final class ZoomHttpClient
{
    /**
     * @param array{method: string, path: string, body: array<string, mixed>|null} $material
     * @return array{status: int, body: string|null, error: string|null}
     */
    public static function execute_webinar_material(array $material): array
    {
        if (! self::ensure_access_token()) {
            return array(
                'status' => 0,
                'body'   => null,
                'error'  => 'not_connected',
            );
        }

        $token = TokenStore::get_access_token();
        if ($token === '') {
            return array(
                'status' => 0,
                'body'   => null,
                'error'  => 'not_connected',
            );
        }

        $url  = self::resolve_url($material['path']);
        $args = array(
            'method'  => strtoupper($material['method']),
            'timeout' => 30,
            'headers' => array(
                'Authorization' => 'Bearer ' . $token,
                'Content-Type'  => 'application/json',
            ),
        );

        if ($material['body'] !== null) {
            $args['body'] = wp_json_encode($material['body']);
        }

        $response = wp_remote_request($url, $args);
        if (is_wp_error($response)) {
            return array(
                'status' => 0,
                'body'   => null,
                'error'  => $response->get_error_code(),
            );
        }

        return array(
            'status' => (int) wp_remote_retrieve_response_code($response),
            'body'   => wp_remote_retrieve_body($response),
            'error'  => null,
        );
    }

    /**
     * @return array{status: int, body: string|null, error: string|null}
     */
    public static function exchange_authorization_code(string $code): array
    {
        $config   = TokenStore::oauth_config();
        $material = build_oauth_token_exchange_request($config, $code);
        return self::execute_oauth_material($material);
    }

    /**
     * @return array{status: int, body: string|null, error: string|null}
     */
    public static function refresh_access_token(): array
    {
        $config = TokenStore::oauth_config();
        $refresh = TokenStore::get_refresh_token();
        if ($refresh === '') {
            return array(
                'status' => 0,
                'body'   => null,
                'error'  => 'missing_refresh_token',
            );
        }
        $material = build_oauth_refresh_request($config, $refresh);
        return self::execute_oauth_material($material);
    }

    /**
     * @param array{method: string, path: string, body: array<string, mixed>|null} $material
     * @return array{status: int, body: string|null, error: string|null}
     */
    private static function execute_oauth_material(array $material): array
    {
        $config = TokenStore::oauth_config();
        if ($config['client_id'] === '' || $config['client_secret'] === '') {
            return array(
                'status' => 0,
                'body'   => null,
                'error'  => 'missing_client_credentials',
            );
        }

        $url     = $material['path'];
        $headers = array(
            'Authorization' => 'Basic ' . base64_encode($config['client_id'] . ':' . $config['client_secret']),
        );

        $args = array(
            'method'  => strtoupper($material['method']),
            'timeout' => 30,
            'headers' => $headers,
        );

        if ($material['body'] !== null) {
            $args['headers']['Content-Type'] = 'application/x-www-form-urlencoded';
            $args['body']                      = http_build_query($material['body'], '', '&', PHP_QUERY_RFC3986);
        }

        $response = wp_remote_request($url, $args);
        if (is_wp_error($response)) {
            return array(
                'status' => 0,
                'body'   => null,
                'error'  => $response->get_error_code(),
            );
        }

        return array(
            'status' => (int) wp_remote_retrieve_response_code($response),
            'body'   => wp_remote_retrieve_body($response),
            'error'  => null,
        );
    }

    private static function resolve_url(string $path): string
    {
        if (str_starts_with($path, 'https://')) {
            return $path;
        }
        return rtrim(Constants::ZOOM_API_BASE, '/') . '/' . ltrim($path, '/');
    }

    public static function ensure_access_token(): bool
    {
        if (TokenStore::get_access_token() === '') {
            return false;
        }

        $expires_at = TokenStore::get_expires_at();
        if ($expires_at > 0 && $expires_at > time() + 60) {
            return true;
        }

        if (TokenStore::get_refresh_token() === '') {
            return true;
        }

        $result = self::refresh_access_token();
        if ($result['status'] < 200 || $result['status'] >= 300 || ! is_string($result['body'])) {
            return false;
        }

        $data = json_decode($result['body'], true);
        if (! is_array($data)) {
            return false;
        }

        TokenStore::persist_token_response($data);
        return isset($data['access_token']) && is_string($data['access_token']) && $data['access_token'] !== '';
    }
}
