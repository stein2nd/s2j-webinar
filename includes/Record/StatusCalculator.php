<?php
/**
 * dirty / synced / not_created / error (docs/data_dictionary.md).
 *
 * @package S2J_Webinar
 */

declare(strict_types=1);

namespace S2J\Webinar\Record;

final class StatusCalculator {

	/**
	 * @param array<string, mixed> $record
	 */
	public static function compute( array $record, int $post_id ): string {
		$webinar_id  = isset( $record['webinar_id'] ) ? trim( (string) $record['webinar_id'] ) : '';
		$last_error  = isset( $record['last_error'] ) ? trim( (string) $record['last_error'] ) : '';
		$meta_status = '';
		if ( $post_id > 0 && function_exists( 'get_post_meta' ) ) {
			$raw = get_post_meta( $post_id, '_s2j_webinar_status', true );
			$meta_status = is_scalar( $raw ) ? (string) $raw : '';
		}

		if ( $webinar_id === '' ) {
			if ( $last_error !== '' || $meta_status === 'error' ) {
				return 'error';
			}
			return 'not_created';
		}

		if ( $meta_status === 'error' && $last_error !== '' ) {
			return 'error';
		}

		$last_sent = array();
		if ( $post_id > 0 && function_exists( 'get_post_meta' ) ) {
			$raw = get_post_meta( $post_id, '_s2j_webinar_last_sent', true );
			if ( is_array( $raw ) ) {
				$last_sent = $raw;
			}
		}
		if ( $last_sent === array() ) {
			return 'dirty';
		}

		if ( self::body_differs( $record, $last_sent ) ) {
			return 'dirty';
		}

		return 'synced';
	}

	/**
	 * @param array<string, mixed> $record
	 * @param array<string, mixed> $last_sent
	 */
	private static function body_differs( array $record, array $last_sent ): bool {
		$keys = array(
			'topic',
			'agenda',
			'start_at',
			'timezone',
			'duration_minutes',
			'auto_recording',
			'approval_type',
		);

		foreach ( $keys as $key ) {
			$current = $record[ $key ] ?? null;
			$sent    = $last_sent[ $key ] ?? null;
			if ( $key === 'approval_type' ) {
				if ( (string) $current !== (string) $sent ) {
					return true;
				}
				continue;
			}
			if ( is_string( $current ) ) {
				$current = trim( $current );
			}
			if ( is_string( $sent ) ) {
				$sent = trim( $sent );
			}
			if ( $current !== $sent ) {
				return true;
			}
		}

		return false;
	}
}
