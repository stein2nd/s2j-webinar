<?php

/**
 * Persist webinar fields on posts (docs/sync_execution_spec.md, data_dictionary.md).
 *
 * @package S2J_Webinar
 */

declare(strict_types=1);

namespace S2J\Webinar\Record;

use S2J\Webinar\GatherPress\OnlineEventLinkWriter;

final class RecordMetaWriter
{
    /**
     * @param array<string, mixed> $record
     */
    public static function write_after_sync(int $post_id, array $record): void
    {
        self::write_scalar_fields($post_id, $record, false);
    }

    /**
     * @param array<string, mixed> $record
     */
    public static function write_after_get(int $post_id, array $record): void
    {
        self::write_scalar_fields($post_id, $record, false);
    }

    /**
     * @param array<string, mixed> $record
     */
    public static function write_after_delete_success(int $post_id, array $record): void
    {
        self::write_scalar_fields($post_id, $record, true);
        OnlineEventLinkWriter::clear($post_id);
        delete_post_meta($post_id, '_s2j_webinar_last_sent');
        delete_post_meta($post_id, '_s2j_webinar_last_panelists');
    }

    /**
     * @param array<string, mixed> $record
     */
    private static function write_scalar_fields(int $post_id, array $record, bool $allow_clear_volatile): void
    {
        $map = array(
            'webinar_id'   => '_s2j_webinar_id',
            'webinar_uuid' => '_s2j_webinar_uuid',
            'join_url'     => '_s2j_webinar_join_url',
            'status'       => '_s2j_webinar_status',
            'last_error'   => '_s2j_webinar_last_error',
        );

        foreach ($map as $field => $meta_key) {
            if (! array_key_exists($field, $record)) {
                continue;
            }
            $value = $record[ $field ];
            if (! is_scalar($value) && $value !== null) {
                continue;
            }
            $string_value = (string) $value;

            if (in_array($field, array( 'join_url', 'webinar_uuid' ), true)) {
                if ($string_value === '' && ! $allow_clear_volatile) {
                    continue;
                }
            }

            update_post_meta($post_id, $meta_key, $string_value);

            if ($field === 'join_url' && $string_value !== '') {
                OnlineEventLinkWriter::set_join_url($post_id, $string_value);
            }
        }
    }

    /**
     * @param array<string, mixed> $record
     */
    public static function persist_step_success(int $post_id, string $op, array $record): void
    {
        if (in_array($op, array( 'create', 'update' ), true)) {
            update_post_meta($post_id, '_s2j_webinar_last_sent', self::last_sent_snapshot($record));
        }
        if (in_array($op, array( 'add_panelists', 'remove_panelists' ), true)) {
            $panelists = isset($record['panelists']) && is_array($record['panelists']) ? $record['panelists'] : array();
            update_post_meta($post_id, '_s2j_webinar_last_panelists', $panelists);
        }
    }

    /**
     * @param array<string, mixed> $record
     * @return array<string, mixed>
     */
    public static function last_sent_snapshot(array $record): array
    {
        $keys = array( 'topic', 'agenda', 'start_at', 'timezone', 'duration_minutes', 'auto_recording', 'approval_type' );
        $out  = array();
        foreach ($keys as $key) {
            if (array_key_exists($key, $record)) {
                $out[ $key ] = $record[ $key ];
            }
        }
        return $out;
    }

    /**
     * @param array<string, mixed> $record
     */
    public static function persist_error(int $post_id, array $record, string $fallback): void
    {
        $message = isset($record['last_error']) && is_string($record['last_error']) && $record['last_error'] !== ''
            ? $record['last_error']
            : $fallback;
        update_post_meta($post_id, '_s2j_webinar_last_error', \S2J\Webinar\Security\ErrorRedaction::scrub($message));
        update_post_meta($post_id, '_s2j_webinar_status', 'error');
    }
}
