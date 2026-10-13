<?php
/**
 * validate → plan → HTTP → map pipeline (docs/sync_execution_spec.md).
 *
 * @package S2J_Webinar
 */

declare(strict_types=1);

namespace S2J\Webinar\Sync;

use S2J\Webinar\Http\WebinarHttpExecutor;
use S2J\Webinar\Messages\DeficiencyMessages;
use S2J\Webinar\OAuth\TokenStore;
use S2J\Webinar\Record\RecordAssembler;
use S2J\Webinar\Record\RecordMetaWriter;
use S2J\Webinar\ServiceBridge;
use WP_Post;

use function S2J\WebinarService\build_webinar_request;
use function S2J\WebinarService\map_webinar_response;
use function S2J\WebinarService\plan_webinar_operations;

final class SyncRunner {

	/**
	 * @return array{ok: bool, message: string, deficiencies?: list<string>, user_messages?: list<string>, record?: array<string, mixed>}
	 */
	public static function sync_post( WP_Post $post, bool $intend_retry = false ): array {
		if ( ! TokenStore::is_connected() ) {
			return array(
				'ok'      => false,
				'message' => 'not_connected',
			);
		}

		$record = RecordAssembler::from_post( $post );

		$validated = ServiceBridge::validate_record( $record );
		if ( $validated['deficiencies'] !== array() ) {
			return array(
				'ok'            => false,
				'message'       => 'validation_failed',
				'deficiencies'  => $validated['deficiencies'],
				'user_messages' => DeficiencyMessages::translate_codes( $validated['deficiencies'] ),
				'record'        => $validated['record'],
			);
		}

		$retry = $intend_retry
			&& ( $validated['record']['status'] ?? '' ) === 'error'
			&& ( $validated['record']['webinar_id'] ?? '' ) !== '';

		$context = SyncContextBuilder::for_sync( (int) $post->ID, $validated['record'], $retry, false );

		$steps = plan_webinar_operations( $validated['record'], $context );
		if ( $steps === array() ) {
			return array(
				'ok'      => true,
				'message' => 'nothing_to_do',
				'record'  => $validated['record'],
			);
		}

		$working = $validated['record'];
		foreach ( $steps as $step ) {
			$op = isset( $step['op'] ) ? (string) $step['op'] : '';
			if ( $op === '' ) {
				continue;
			}

			$built = build_webinar_request( $op, $working, $step );
			if ( $built['deficiencies'] !== array() ) {
				RecordMetaWriter::persist_error( (int) $post->ID, $working, 'plan_step_failed' );
				return array(
					'ok'            => false,
					'message'       => 'plan_step_failed',
					'deficiencies'  => $built['deficiencies'],
					'user_messages' => DeficiencyMessages::translate_codes( $built['deficiencies'] ),
					'record'        => $working,
				);
			}

			if ( ! isset( $built['material'] ) || ! is_array( $built['material'] ) ) {
				continue;
			}

			$http = WebinarHttpExecutor::execute( $built['material'] );
			$body = is_string( $http['body'] ) ? json_decode( $http['body'], true ) : null;
			if ( ! is_array( $body ) ) {
				$body = null;
			}

			$mapped = map_webinar_response( $op, (int) $http['status'], $body, $working );
			$working = $mapped['record'];

			if ( $mapped['deficiencies'] !== array() || ( $working['status'] ?? '' ) === 'error' ) {
				RecordMetaWriter::persist_error( (int) $post->ID, $working, 'sync_failed' );
				return array(
					'ok'            => false,
					'message'       => 'sync_failed',
					'deficiencies'  => $mapped['deficiencies'],
					'user_messages' => DeficiencyMessages::translate_codes( $mapped['deficiencies'] ),
					'record'        => $working,
				);
			}

			RecordMetaWriter::persist_step_success( (int) $post->ID, $op, $working );
		}

		RecordMetaWriter::write_after_sync( (int) $post->ID, $working );

		return array(
			'ok'      => true,
			'message' => 'synced',
			'record'  => $working,
		);
	}
}
