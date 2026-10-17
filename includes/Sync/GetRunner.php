<?php

/**
 * Zoom で開く — get only (docs/sync_execution_spec.md).
 *
 * @package S2J_Webinar
 */

declare(strict_types=1);

namespace S2J\Webinar\Sync;

use S2J\Webinar\Http\WebinarHttpExecutor;
use S2J\Webinar\OAuth\TokenStore;
use S2J\Webinar\Record\RecordAssembler;
use S2J\Webinar\Record\RecordMetaWriter;
use S2J\Webinar\ServiceBridge;
use WP_Post;

use function S2J\WebinarService\map_webinar_response;

final class GetRunner
{
    /**
     * @return array{ok: bool, message: string, start_url?: string, record?: array<string, mixed>}
     */
    public static function open_in_zoom(WP_Post $post): array
    {
        if (! TokenStore::is_connected()) {
            return array(
                'ok'      => false,
                'message' => 'not_connected',
            );
        }

        $record = RecordAssembler::from_post($post);
        $webinar_id = isset($record['webinar_id']) ? trim((string) $record['webinar_id']) : '';
        if ($webinar_id === '') {
            return array(
                'ok'      => false,
                'message' => 'webinar_id_empty',
            );
        }

        $built = ServiceBridge::build_request('get', $record, array( 'op' => 'get' ));
        if ($built['deficiencies'] !== array() || ! isset($built['material']) || ! is_array($built['material'])) {
            return array(
                'ok'      => false,
                'message' => 'get_build_failed',
            );
        }

        $http = WebinarHttpExecutor::execute($built['material']);
        $body = is_string($http['body']) ? json_decode($http['body'], true) : null;
        if (! is_array($body)) {
            $body = null;
        }

        $mapped = map_webinar_response('get', (int) $http['status'], $body, $record);
        $working = $mapped['record'];

        if ($mapped['deficiencies'] !== array() || (int) $http['status'] < 200 || (int) $http['status'] >= 300) {
            RecordMetaWriter::persist_error((int) $post->ID, $working, 'get_failed');
            return array(
                'ok'      => false,
                'message' => 'get_failed',
                'record'  => $working,
            );
        }

        RecordMetaWriter::write_after_get((int) $post->ID, $working);

        $start_url = isset($mapped['start_url']) ? (string) $mapped['start_url'] : '';

        return array(
            'ok'        => true,
            'message'   => 'get_ok',
            'start_url' => $start_url,
            'record'    => $working,
        );
    }
}
