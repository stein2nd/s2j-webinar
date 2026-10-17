<?php

/**
 * Read event start, timezone, duration from GatherPress when available.
 *
 * @package S2J_Webinar
 */

declare(strict_types=1);

namespace S2J\Webinar\GatherPress;

final class EventSchedule
{
    /**
     * @return array{start_at: string, timezone: string, duration_minutes: int}
     */
    public static function read(int $post_id): array
    {
        $defaults = self::read_gatherpress_meta($post_id);

        /**
         * @param array{start_at: string, timezone: string, duration_minutes: int} $schedule
         * @param int $post_id
         */
        $filtered = apply_filters('s2j_webinar_gatherpress_schedule', $defaults, $post_id);
        if (! is_array($filtered)) {
            return $defaults;
        }

        return array(
            'start_at'         => isset($filtered['start_at']) ? (string) $filtered['start_at'] : '',
            'timezone'         => isset($filtered['timezone']) ? (string) $filtered['timezone'] : wp_timezone_string(),
            'duration_minutes' => isset($filtered['duration_minutes']) ? (int) $filtered['duration_minutes'] : 0,
        );
    }

    /**
     * @return array{start_at: string, timezone: string, duration_minutes: int}
     */
    private static function read_gatherpress_meta(int $post_id): array
    {
        $defaults = array(
            'start_at'         => '',
            'timezone'         => function_exists('wp_timezone_string') ? wp_timezone_string() : 'UTC',
            'duration_minutes' => 0,
        );

        if ($post_id <= 0 || ! function_exists('get_post_meta')) {
            return $defaults;
        }

        if (class_exists('\GatherPress\Core\Event\Event')) {
            $event = new \GatherPress\Core\Event\Event($post_id);
            $datetime = $event->get_datetime();
            if (self::has_event_datetime($datetime)) {
                return self::normalize_from_gatherpress_event_row($datetime, $defaults);
            }
        }

        $raw = get_post_meta($post_id, 'gatherpress_datetime', true);
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return self::normalize_from_gatherpress_json($decoded, $defaults);
            }
        }

        if (is_array($raw)) {
            return self::normalize_from_gatherpress_legacy_array($raw, $defaults);
        }

        return $defaults;
    }

    /**
     * @param array<string, mixed> $datetime
     */
    private static function has_event_datetime(array $datetime): bool
    {
        return ( isset($datetime['datetime_start']) && (string) $datetime['datetime_start'] !== '' )
            || ( isset($datetime['dateTime']) && (string) $datetime['dateTime'] !== '' );
    }

    /**
     * @param array<string, mixed> $datetime GatherPress\Event\Event::get_datetime() shape.
     * @param array{start_at: string, timezone: string, duration_minutes: int} $defaults
     * @return array{start_at: string, timezone: string, duration_minutes: int}
     */
    private static function normalize_from_gatherpress_event_row(array $datetime, array $defaults): array
    {
        $timezone = isset($datetime['timezone']) ? (string) $datetime['timezone'] : $defaults['timezone'];
        $start    = isset($datetime['datetime_start']) ? (string) $datetime['datetime_start'] : '';
        $end      = isset($datetime['datetime_end']) ? (string) $datetime['datetime_end'] : '';

        $start_at = self::format_start_at($start, $timezone);
        $minutes  = self::duration_minutes_between($start, $end, $timezone);

        return array(
            'start_at'         => $start_at,
            'timezone'         => $timezone !== '' ? $timezone : $defaults['timezone'],
            'duration_minutes' => $minutes,
        );
    }

    /**
     * @param array<string, mixed> $data JSON decoded gatherpress_datetime (dateTimeStart / dateTimeEnd).
     * @param array{start_at: string, timezone: string, duration_minutes: int} $defaults
     * @return array{start_at: string, timezone: string, duration_minutes: int}
     */
    private static function normalize_from_gatherpress_json(array $data, array $defaults): array
    {
        $timezone = isset($data['timezone']) ? (string) $data['timezone'] : $defaults['timezone'];
        $start    = isset($data['dateTimeStart']) ? (string) $data['dateTimeStart'] : '';
        $end      = isset($data['dateTimeEnd']) ? (string) $data['dateTimeEnd'] : '';

        if ($start === '' && isset($data['dateTime'])) {
            $start = (string) $data['dateTime'];
        }

        $duration = isset($data['duration']) ? (int) $data['duration'] : 0;
        if ($duration <= 0) {
            $duration = self::duration_minutes_between($start, $end, $timezone);
        }

        return array(
            'start_at'         => self::format_start_at($start, $timezone),
            'timezone'         => $timezone !== '' ? $timezone : $defaults['timezone'],
            'duration_minutes' => max(0, $duration),
        );
    }

    /**
     * @param array<string, mixed> $datetime Legacy array meta (dateTime + duration).
     * @param array{start_at: string, timezone: string, duration_minutes: int} $defaults
     * @return array{start_at: string, timezone: string, duration_minutes: int}
     */
    private static function normalize_from_gatherpress_legacy_array(array $datetime, array $defaults): array
    {
        $start = isset($datetime['dateTime']) ? (string) $datetime['dateTime'] : '';
        if ($start === '' && isset($datetime['start'])) {
            $start = (string) $datetime['start'];
        }
        $timezone = isset($datetime['timezone']) ? (string) $datetime['timezone'] : $defaults['timezone'];
        $duration = isset($datetime['duration']) ? (int) $datetime['duration'] : 0;

        $start_at = $start;
        if (str_contains($start, 'T') === false) {
            $start_at = self::format_start_at($start, $timezone);
        }

        return array(
            'start_at'         => $start_at,
            'timezone'         => $timezone !== '' ? $timezone : $defaults['timezone'],
            'duration_minutes' => max(0, $duration),
        );
    }

    private static function format_start_at(string $local_datetime, string $timezone): string
    {
        if ($local_datetime === '') {
            return '';
        }

        if (str_contains($local_datetime, 'T')) {
            return $local_datetime;
        }

        try {
            $tz = new \DateTimeZone($timezone !== '' ? $timezone : 'UTC');
            $dt = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $local_datetime, $tz);
            if ($dt === false) {
                $dt = new \DateTimeImmutable($local_datetime, $tz);
            }

            return $dt->format('c');
        } catch (\Exception $e) {
            unset($e);
            return $local_datetime;
        }
    }

    private static function duration_minutes_between(string $start, string $end, string $timezone): int
    {
        if ($start === '' || $end === '') {
            return 0;
        }

        try {
            $tz       = new \DateTimeZone($timezone !== '' ? $timezone : 'UTC');
            $start_dt = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $start, $tz);
            $end_dt   = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $end, $tz);
            if ($start_dt === false || $end_dt === false) {
                return 0;
            }
            $seconds = $end_dt->getTimestamp() - $start_dt->getTimestamp();

            return max(0, (int) floor($seconds / 60));
        } catch (\Exception $e) {
            unset($e);
            return 0;
        }
    }
}
