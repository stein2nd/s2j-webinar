<?php
/**
 * plan_webinar_operations context (docs/sync_execution_spec.md).
 *
 * @package S2J_Webinar
 */

declare(strict_types=1);

namespace S2J\Webinar\Sync;

final class SyncContextBuilder {

	/**
	 * @param array<string, mixed> $record Validated record (status may be error).
	 * @return array<string, mixed>
	 */
	public static function for_sync( int $post_id, array $record, bool $intend_retry, bool $intend_delete ): array {
		$context = array(
			'previous_panelists' => self::read_previous_panelists( $post_id ),
			'survey_document'    => self::read_ready_survey_document( $post_id ),
		);

		$topic = isset( $record['topic'] ) ? trim( (string) $record['topic'] ) : '';
		if ( $topic !== '' ) {
			$context['webinar_title'] = $topic;
		}

		if ( $intend_delete ) {
			$context['intend_delete'] = true;
		}

		if ( $intend_retry ) {
			$context['intend_retry'] = true;
		}

		return $context;
	}

	/**
	 * @return list<array{name: string, email: string}>
	 */
	public static function read_previous_panelists( int $post_id ): array {
		if ( $post_id <= 0 || ! function_exists( 'get_post_meta' ) ) {
			return array();
		}
		$raw = get_post_meta( $post_id, '_s2j_webinar_last_panelists', true );
		if ( ! is_array( $raw ) ) {
			return array();
		}
		$out = array();
		foreach ( $raw as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$email = isset( $row['email'] ) ? (string) $row['email'] : '';
			if ( $email === '' ) {
				continue;
			}
			$out[] = array(
				'name'  => isset( $row['name'] ) ? (string) $row['name'] : '',
				'email' => $email,
			);
		}
		return $out;
	}

	/**
	 * @return array<string, mixed>|null
	 */
	public static function read_ready_survey_document( int $post_id ): ?array {
		if ( $post_id <= 0 || ! function_exists( 'get_post_meta' ) ) {
			return null;
		}
		$survey = get_post_meta( $post_id, '_s2j_webinar_survey', true );
		if ( ! is_array( $survey ) ) {
			return null;
		}
		$status = isset( $survey['status'] ) ? (string) $survey['status'] : '';
		if ( $status !== 'ready' ) {
			return null;
		}
		return $survey;
	}
}
