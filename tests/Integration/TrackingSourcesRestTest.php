<?php

declare(strict_types=1);

namespace S2J\Webinar\Tests\Integration;

use S2J\Webinar\Http\WebinarHttpExecutor;
use WP_REST_Request;

final class TrackingSourcesRestTest extends GatherPressEventTestCase {

	private const ZOOM_TRACKING_FIXTURE = '{"tracking_sources":[{"id":"ts-1","source_name":"web","tracking_url":"https://zoom.us/w/register/web"}]}';

	protected function tearDown(): void {
		WebinarHttpExecutor::$executor = null;
		delete_option( 's2j_webinar_zoom_access_token' );
		parent::tearDown();
	}

	public function test_tracking_sources_requires_webinar_id(): void {
		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		$post_id = $this->create_event_post();

		$request  = new WP_REST_Request( 'GET', '/s2j-webinar/v1/events/' . $post_id . '/tracking-sources' );
		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 400, $response->get_status() );
		$data = $response->get_data();
		$this->assertSame( 'tracking_webinar_id_empty', $data['code'] ?? '' );
	}

	public function test_tracking_sources_oauth_disconnected(): void {
		delete_option( 's2j_webinar_zoom_access_token' );

		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		$post_id = $this->create_event_post();
		update_post_meta( $post_id, '_s2j_webinar_id', '9001' );

		$request  = new WP_REST_Request( 'GET', '/s2j-webinar/v1/events/' . $post_id . '/tracking-sources' );
		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 503, $response->get_status() );
		$data = $response->get_data();
		$this->assertSame( 'oauth_disconnected', $data['code'] ?? '' );
	}

	public function test_tracking_sources_returns_mapped_items_with_mock_http(): void {
		update_option( 's2j_webinar_zoom_access_token', 'phpunit-token' );

		WebinarHttpExecutor::$executor = static function ( array $material ): array {
			if ( str_contains( $material['path'], '/tracking_sources' ) ) {
				return array(
					'status' => 200,
					'body'   => self::ZOOM_TRACKING_FIXTURE,
					'error'  => null,
				);
			}

			return array(
				'status' => 500,
				'body'   => null,
				'error'  => 'unexpected path',
			);
		};

		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		$post_id = $this->create_event_post();
		update_post_meta( $post_id, '_s2j_webinar_id', '9001' );

		$request  = new WP_REST_Request( 'GET', '/s2j-webinar/v1/events/' . $post_id . '/tracking-sources' );
		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 200, $response->get_status(), (string) wp_json_encode( $response->get_data() ) );
		$data  = $response->get_data();
		$items = $data['items'] ?? null;
		$this->assertIsArray( $items );
		$this->assertCount( 1, $items );
		$this->assertSame( 'web', $items[0]['source_name'] ?? '' );
		$this->assertSame( 'https://zoom.us/w/register/web', $items[0]['registration_url'] ?? '' );
		$this->assertSame( 'ts-1', $items[0]['external_id'] ?? '' );
	}

	public function test_tracking_sources_empty_list_is_200(): void {
		update_option( 's2j_webinar_zoom_access_token', 'phpunit-token' );

		WebinarHttpExecutor::$executor = static function ( array $material ): array {
			if ( str_contains( $material['path'], '/tracking_sources' ) ) {
				return array(
					'status' => 200,
					'body'   => '{"tracking_sources":[]}',
					'error'  => null,
				);
			}

			return array(
				'status' => 500,
				'body'   => null,
				'error'  => 'unexpected path',
			);
		};

		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		$post_id = $this->create_event_post();
		update_post_meta( $post_id, '_s2j_webinar_id', '9001' );

		$request  = new WP_REST_Request( 'GET', '/s2j-webinar/v1/events/' . $post_id . '/tracking-sources' );
		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertSame( array(), $data['items'] ?? null );
	}
}
