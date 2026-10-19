<?php

/**
 * GET tracking-sources pipeline (Service build/map + plugin HTTP).
 *
 * @package S2J_Webinar
 */

declare(strict_types=1);

namespace S2J\Webinar\SourceQr;

use S2J\Webinar\Constants;
use S2J\Webinar\Http\WebinarHttpExecutor;
use S2J\Webinar\OAuth\TokenStore;
use S2J\Webinar\Provider\WebinarProvider;
use WP_Error;

use function S2J\WebinarService\build_tracking_sources_request;
use function S2J\WebinarService\map_tracking_sources_response;

final class TrackingSourcesImport
{
    /**
     * @return array{items: list<array<string, mixed>>}|WP_Error
     */
    public static function fetch_for_event(int $post_id): array|WP_Error
    {
        $webinar_id = trim((string) get_post_meta($post_id, '_s2j_webinar_id', true));
        if ($webinar_id === '') {
            return new WP_Error(
                'tracking_webinar_id_empty',
                __('Connect this event to a webinar before loading tracking sources.', 's2j-webinar'),
                array( 'status' => 400 )
            );
        }

        $provider = trim((string) get_post_meta($post_id, '_s2j_webinar_provider', true));
        if ($provider === '') {
            $provider = Constants::DEFAULT_PROVIDER;
        }

        if ($provider !== Constants::DEFAULT_PROVIDER) {
            return new WP_Error(
                'provider_import_not_supported',
                __('Tracking import is not supported for this provider yet.', 's2j-webinar'),
                array( 'status' => 501 )
            );
        }

        $built = build_tracking_sources_request($provider, $webinar_id);
        $build_error = self::deficiencies_to_error($built['deficiencies'], 'build');
        if ($build_error instanceof WP_Error) {
            return $build_error;
        }

        if (! isset($built['material'])) {
            return self::upstream_error();
        }
        $material = $built['material'];

        if (! TokenStore::is_connected()) {
            return self::oauth_disconnected_error();
        }

        $http = WebinarHttpExecutor::execute($material);
        if ((int) $http['status'] === 0 || ( $http['error'] ?? null ) === 'not_connected') {
            return self::oauth_disconnected_error();
        }

        $body = is_string($http['body']) ? $http['body'] : '';
        $mapped = map_tracking_sources_response($provider, (int) $http['status'], $body);
        $deficiencies = $mapped['deficiencies'];

        if ($deficiencies === array( 'tracking_sources_empty' )) {
            return array( 'items' => array() );
        }

        $map_error = self::deficiencies_to_error($deficiencies, 'map');
        if ($map_error instanceof WP_Error) {
            return $map_error;
        }

        return array( 'items' => $mapped['items'] );
    }

    /**
     * @param list<string> $deficiencies
     */
    private static function deficiencies_to_error(array $deficiencies, string $phase): ?WP_Error
    {
        if ($deficiencies === array()) {
            return null;
        }

        $code = $deficiencies[0];

        if ($code === 'tracking_webinar_id_empty') {
            return new WP_Error(
                'tracking_webinar_id_empty',
                __('Connect this event to a webinar before loading tracking sources.', 's2j-webinar'),
                array( 'status' => 400 )
            );
        }

        if ($code === 'provider_unsupported') {
            return new WP_Error(
                'provider_unsupported',
                __('This webinar provider is not supported.', 's2j-webinar'),
                array( 'status' => 501 )
            );
        }

        if (in_array($code, array( 'tracking_http_error', 'tracking_body_invalid' ), true) || $phase === 'map') {
            return self::upstream_error();
        }

        return self::upstream_error();
    }

    private static function oauth_disconnected_error(): WP_Error
    {
        return new WP_Error(
            'oauth_disconnected',
            sprintf(
                /* translators: %s: webinar provider display name, e.g. Zoom */
                __('Connect %s in settings before loading tracking sources.', 's2j-webinar'),
                WebinarProvider::label()
            ),
            array( 'status' => 503 )
        );
    }

    private static function upstream_error(): WP_Error
    {
        return new WP_Error(
            'tracking_upstream_error',
            sprintf(
                /* translators: %s: webinar provider display name, e.g. Zoom */
                __('Tracking sources could not be loaded from %s. Try again later.', 's2j-webinar'),
                WebinarProvider::label()
            ),
            array( 'status' => 502 )
        );
    }
}
