<?php
/**
 * Zoom から削除 — intend_delete plan (docs/sync_execution_spec.md).
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

final class DeleteRunner {

	/**
	 * @return array{ok: bool, message: string, user_messages?: list<string>, record?: array<string, mixed>}
	 */
	public static function delete_from_zoom( WP_Post $post ): array {
		if ( ! TokenStore::is_connected() ) {
			return array(
				'ok'      => false,
				'message' => 'not_connected',
			);
		}

		$record = RecordAssembler::from_post( $post );
		$validated = ServiceBridge::validate_record( $record );

		$context = SyncContextBuilder::for_sync( (int) $post->ID, $validated['record'], false, true );
		$steps   = plan_webinar_operations( $validated['record'], $context );

		$working = $validated['record'];
		foreach ( $steps as $step ) {
			$op = isset( $step['op'] ) ? (string) $step['op'] : '';
			if ( $op !== 'delete' ) {
				continue;
			}

			$built = build_webinar_request( $op, $working, $step );
			if ( $built['deficiencies'] !== array() || ! isset( $built['material'] ) || ! is_array( $built['material'] ) ) {
				return array(
					'ok'            => false,
					'message'       => 'delete_build_failed',
					'user_messages' => DeficiencyMessages::translate_codes( $built['deficiencies'] ?? array() ),
				);
			}

			$http = WebinarHttpExecutor::execute( $built['material'] );
			$body = is_string( $http['body'] ) ? json_decode( $http['body'], true ) : null;
			if ( ! is_array( $body ) ) {
				$body = null;
			}

			$mapped  = map_webinar_response( 'delete', (int) $http['status'], $body, $working );
			$working = $mapped['record'];

			if ( $mapped['deficiencies'] !== array() || ( $working['status'] ?? '' ) === 'error' ) {
				RecordMetaWriter::persist_error( (int) $post->ID, $working, 'delete_failed' );
				return array(
					'ok'      => false,
					'message' => 'delete_failed',
					'record'  => $working,
				);
			}

			RecordMetaWriter::write_after_delete_success( (int) $post->ID, $working );
			return array(
				'ok'      => true,
				'message' => 'deleted',
				'record'  => $working,
			);
		}

		return array(
			'ok'      => false,
			'message' => 'nothing_to_delete',
		);
	}
}
