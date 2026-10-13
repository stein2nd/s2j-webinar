<?php
/**
 * Zoom webhook endpoint (docs/webhook_spec.md).
 *
 * @package S2J_Webinar
 */

declare(strict_types=1);

namespace S2J\Webinar\Rest;

use S2J\Webinar\Constants;
use S2J\Webinar\GatherPress\OnlineEventLinkWriter;
use S2J\Webinar\Security\Hash;
use WP_REST_Request;
use WP_REST_Response;

final class WebhookController {

	public static function register(): void {
		add_action( 'rest_api_init', array( self::class, 'register_routes' ) );
	}

	public static function register_routes(): void {
		register_rest_route(
			Constants::REST_NAMESPACE,
			'/webhook/zoom',
			array(
				'methods'             => 'POST',
				'callback'            => array( self::class, 'handle' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	public static function handle( WP_REST_Request $request ): WP_REST_Response {
		$secret = (string) get_option( 's2j_webinar_webhook_secret', '' );
		if ( $secret === '' ) {
			return new WP_REST_Response( array( 'ok' => false ), 503 );
		}

		$payload = $request->get_json_params();
		if ( ! is_array( $payload ) ) {
			return new WP_REST_Response( array( 'ok' => false ), 400 );
		}

		if ( isset( $payload['event'] ) && $payload['event'] === 'endpoint.url_validation' ) {
			$plain = isset( $payload['payload']['plainToken'] ) ? (string) $payload['payload']['plainToken'] : '';
			if ( $plain === '' ) {
				return new WP_REST_Response( array( 'ok' => false ), 400 );
			}
			$hash = Hash::hmac_sha256( $plain, $secret );
			return new WP_REST_Response(
				array(
					'plainToken'     => $plain,
					'encryptedToken' => $hash,
				),
				200
			);
		}

		if ( ! self::verify_signature( $request, $secret ) ) {
			return new WP_REST_Response( array( 'ok' => false ), 401 );
		}

		$event = isset( $payload['event'] ) ? (string) $payload['event'] : '';
		$object = isset( $payload['payload']['object'] ) && is_array( $payload['payload']['object'] )
			? $payload['payload']['object']
			: array();
		$webinar_id = isset( $object['id'] ) ? (string) $object['id'] : '';

		if ( $webinar_id === '' ) {
			return new WP_REST_Response( array( 'ok' => true, 'ignored' => true ), 200 );
		}

		$post_id = self::find_post_by_webinar_id( $webinar_id );
		if ( $post_id <= 0 ) {
			return new WP_REST_Response( array( 'ok' => true, 'ignored' => true ), 200 );
		}

		if ( $event === 'webinar.started' ) {
			self::set_session_phase( $post_id, 'started' );
			$join = (string) get_post_meta( $post_id, '_s2j_webinar_join_url', true );
			if ( $join !== '' ) {
				OnlineEventLinkWriter::set_join_url( $post_id, $join );
			}
		} elseif ( $event === 'webinar.ended' ) {
			self::set_session_phase( $post_id, 'ended' );
		}

		return new WP_REST_Response( array( 'ok' => true ), 200 );
	}

	private static function verify_signature( WP_REST_Request $request, string $secret ): bool {
		$signature = $request->get_header( 'x-zm-signature' );
		$timestamp = $request->get_header( 'x-zm-request-timestamp' );
		if ( ! is_string( $signature ) || ! is_string( $timestamp ) ) {
			return false;
		}
		$body = $request->get_body();
		if ( ! is_string( $body ) ) {
			return false;
		}
		$message = 'v0:' . $timestamp . ':' . $body;
		$hash    = 'v0=' . Hash::hmac_sha256( $message, $secret );
		return Hash::equals( $hash, $signature );
	}

	private static function find_post_by_webinar_id( string $webinar_id ): int {
		$query = new \WP_Query(
			array(
				'post_type'      => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_query'     => array(
					array(
						'key'   => '_s2j_webinar_id',
						'value' => $webinar_id,
					),
				),
			)
		);
		if ( ! $query->have_posts() ) {
			return 0;
		}
		$ids = $query->posts;
		return is_array( $ids ) && isset( $ids[0] ) ? (int) $ids[0] : 0;
	}

	private static function set_session_phase( int $post_id, string $phase ): void {
		update_post_meta(
			$post_id,
			'_s2j_webinar_session',
			array(
				'phase' => $phase,
			)
		);
	}
}
