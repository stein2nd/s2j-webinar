<?php

/**
 * Build WebinarRecord from post meta and GatherPress context.
 *
 * @package S2J_Webinar
 */

declare(strict_types=1);

namespace S2J\Webinar\Record;

use S2J\Webinar\Constants;
use S2J\Webinar\GatherPress\EventSchedule;
use WP_Post;

final class RecordAssembler
{
    /**
     * @return array<string, mixed>
     */
    public static function from_post(WP_Post $post): array
    {
        $post_id = (int) $post->ID;
        $schedule = EventSchedule::read($post_id);

        $record = array(
            'provider'         => self::meta_string($post_id, '_s2j_webinar_provider', Constants::DEFAULT_PROVIDER),
            'topic'            => (string) $post->post_title,
            'agenda'           => (string) $post->post_excerpt,
            'start_at'         => $schedule['start_at'],
            'timezone'         => $schedule['timezone'],
            'duration_minutes' => $schedule['duration_minutes'],
            'auto_recording'   => self::meta_string($post_id, '_s2j_webinar_auto_recording', 'cloud'),
            'approval_type'    => self::meta_approval($post_id),
            'panelists'        => get_post_meta($post_id, '_s2j_webinar_panelists', true),
            'webinar_id'       => self::meta_string($post_id, '_s2j_webinar_id', ''),
            'webinar_uuid'     => self::meta_string($post_id, '_s2j_webinar_uuid', ''),
            'join_url'         => self::meta_string($post_id, '_s2j_webinar_join_url', ''),
            'status'           => self::meta_string($post_id, '_s2j_webinar_status', 'not_created'),
            'last_error'       => self::meta_string($post_id, '_s2j_webinar_last_error', ''),
        );

        if (! is_array($record['panelists'])) {
            $record['panelists'] = array();
        }

        $record['status'] = StatusCalculator::compute($record, $post_id);

        return $record;
    }

    private static function meta_string(int $post_id, string $key, string $default): string
    {
        $value = get_post_meta($post_id, $key, true);
        if (! is_scalar($value) || (string) $value === '') {
            return $default;
        }
        return trim((string) $value);
    }

    private static function meta_approval(int $post_id): int|string
    {
        $value = get_post_meta($post_id, '_s2j_webinar_approval_type', true);
        if ($value === '' || $value === false) {
            return 0;
        }
        if (is_numeric($value)) {
            return (int) $value;
        }
        return (string) $value;
    }
}
