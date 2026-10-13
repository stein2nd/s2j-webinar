<?php
/**
 * Read event start, timezone, duration from GatherPress when available.
 *
 * @package S2J_Webinar
 */

declare(strict_types=1);

namespace S2J\Webinar\GatherPress;

final class EventSchedule {

	/**
	 * @return array{start_at: string, timezone: string, duration_minutes: int}
	 */
	public static function read( int $post_id ): array {
		$defaults = self::read_gatherpress_meta( $post_id );

		/**
		 * @param array{start_at: string, timezone: string, duration_minutes: int} $schedule
		 * @param int $post_id
		 */
		$filtered = apply_filters( 's2j_webinar_gatherpress_schedule', $defaults, $post_id );
		if ( ! is_array( $filtered ) ) {
			return $defaults;
		}

		return array(
			'start_at'         => isset( $filtered['start_at'] ) ? (string) $filtered['start_at'] : '',
			'timezone'         => isset( $filtered['timezone'] ) ? (string) $filtered['timezone'] : wp_timezone_string(),
			'duration_minutes' => isset( $filtered['duration_minutes'] ) ? (int) $filtered['duration_minutes'] : 0,
		);
	}

	/**
	 * @return array{start_at: string, timezone: string, duration_minutes: int}
	 */
	private static function read_gatherpress_meta( int $post_id ): array {
		$defaults = array(
			'start_at'         => '',
			'timezone'         => function_exists( 'wp_timezone_string' ) ? wp_timezone_string() : 'UTC',
			'duration_minutes' => 0,
		);

		if ( $post_id <= 0 || ! function_exists( 'get_post_meta' ) ) {
			return $defaults;
		}

		if ( class_exists( '\GatherPress\Core\Event' ) && method_exists( '\GatherPress\Core\Event', 'get_instance' ) ) {
			$event = \GatherPress\Core\Event::get_instance( $post_id );
			if ( is_object( $event ) && method_exists( $event, 'get_datetime' ) ) {
				$datetime = $event->get_datetime();
				if ( is_array( $datetime ) ) {
					return self::normalize_from_gatherpress_array( $datetime, $defaults );
				}
			}
		}

		$raw = get_post_meta( $post_id, 'gatherpress_datetime', true );
		if ( is_array( $raw ) ) {
			return self::normalize_from_gatherpress_array( $raw, $defaults );
		}

		return $defaults;
	}

	/**
	 * @param array<string, mixed> $datetime
	 * @param array{start_at: string, timezone: string, duration_minutes: int} $defaults
	 * @return array{start_at: string, timezone: string, duration_minutes: int}
	 */
	private static function normalize_from_gatherpress_array( array $datetime, array $defaults ): array {
		$start = isset( $datetime['dateTime'] ) ? (string) $datetime['dateTime'] : '';
		if ( $start === '' && isset( $datetime['start'] ) ) {
			$start = (string) $datetime['start'];
		}
		$timezone = isset( $datetime['timezone'] ) ? (string) $datetime['timezone'] : $defaults['timezone'];
		$duration = isset( $datetime['duration'] ) ? (int) $datetime['duration'] : 0;

		return array(
			'start_at'         => $start,
			'timezone'         => $timezone !== '' ? $timezone : $defaults['timezone'],
			'duration_minutes' => max( 0, $duration ),
		);
	}
}
