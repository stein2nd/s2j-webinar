<?php

declare(strict_types=1);

namespace S2J\Webinar\Tests\Integration;

use S2J\Webinar\Http\WebinarHttpExecutor;
use WP_REST_Request;

/**
 * REST sync + Service pipeline without Zoom Marketplace (mock HTTP, fake site token).
 */
final class SyncRestServiceIntegrationTest extends GatherPressEventTestCase {

	protected function tearDown(): void {
		WebinarHttpExecutor::$executor = null;
		delete_option( 's2j_webinar_zoom_access_token' );
		parent::tearDown();
	}

	public function test_sync_rest_returns_not_connected_without_oauth(): void {
		delete_option( 's2j_webinar_zoom_access_token' );

		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		$post_id = $this->create_event_post( 'REST sync not connected' );
		$this->seed_event_meta_for_service( $post_id );

		$request  = new WP_REST_Request( 'POST', '/s2j-webinar/v1/events/' . $post_id . '/sync' );
		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 422, $response->get_status() );
		$data = $response->get_data();
		$this->assertIsArray( $data );
		$this->assertFalse( $data['ok'] ?? true );
		$this->assertSame( 'not_connected', $data['message'] ?? '' );
	}

	public function test_sync_rest_runs_service_pipeline_with_mock_http(): void {
		update_option( 's2j_webinar_zoom_access_token', 'phpunit-token-without-marketplace' );

		WebinarHttpExecutor::$executor = static function ( array $material ): array {
			if ( str_contains( $material['path'], '/users/me/webinars' ) ) {
				return array(
					'status' => 201,
					'body'   => wp_json_encode(
						array(
							'id'       => '77001',
							'uuid'     => 'uuid-77001',
							'join_url' => 'https://zoom.us/j/77001',
						)
					),
					'error'  => null,
				);
			}
			if ( str_contains( $material['path'], '/panelists' ) ) {
				return array(
					'status' => 201,
					'body'   => '{}',
					'error'  => null,
				);
			}

			return array(
				'status' => 500,
				'body'   => null,
				'error'  => 'unexpected path in test mock',
			);
		};

		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		$post_id = $this->create_event_post( 'REST sync mock' );
		$this->seed_event_meta_for_service( $post_id );

		$request  = new WP_REST_Request( 'POST', '/s2j-webinar/v1/events/' . $post_id . '/sync' );
		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 200, $response->get_status(), (string) wp_json_encode( $response->get_data() ) );
		$data = $response->get_data();
		$this->assertTrue( $data['ok'] ?? false );
		$this->assertSame( '77001', get_post_meta( $post_id, '_s2j_webinar_id', true ) );
		$this->assertSame( 'synced', get_post_meta( $post_id, '_s2j_webinar_status', true ) );
	}

	private function seed_event_meta_for_service( int $post_id ): void {
		update_post_meta( $post_id, 'gatherpress_datetime_start', '2026-12-15 14:30:00' );
		update_post_meta( $post_id, 'gatherpress_datetime_end', '2026-12-15 15:30:00' );
		update_post_meta( $post_id, 'gatherpress_timezone', 'Asia/Tokyo' );
		update_post_meta( $post_id, '_s2j_webinar_provider', 'zoom' );
		update_post_meta( $post_id, '_s2j_webinar_auto_recording', 'cloud' );
		update_post_meta( $post_id, '_s2j_webinar_approval_type', '0' );
		update_post_meta( $post_id, '_s2j_webinar_panelists', array(
			array(
				'name'  => 'Panelist',
				'email' => 'panelist@example.com',
			),
		) );
	}
}
