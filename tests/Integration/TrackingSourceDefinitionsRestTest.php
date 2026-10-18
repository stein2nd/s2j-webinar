<?php

declare(strict_types=1);

namespace S2J\Webinar\Tests\Integration;

final class TrackingSourceDefinitionsRestTest extends GatherPressEventTestCase {

	public function test_definitions_requires_webinar_id(): void {
		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		$post_id = $this->create_event_post();

		$request  = new \WP_REST_Request(
			'GET',
			'/s2j-webinar/v1/events/' . $post_id . '/tracking-source-definitions'
		);
		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 400, $response->get_status() );
		$data = $response->get_data();
		$this->assertSame( 'tracking_webinar_id_empty', $data['code'] ?? '' );
	}

	public function test_definitions_returns_zoom_defaults(): void {
		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		$post_id = $this->create_event_post();
		update_post_meta( $post_id, '_s2j_webinar_id', '100' );

		$request  = new \WP_REST_Request(
			'GET',
			'/s2j-webinar/v1/events/' . $post_id . '/tracking-source-definitions'
		);
		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 200, $response->get_status(), (string) wp_json_encode( $response->get_data() ) );
		$data    = $response->get_data();
		$sources = $data['sources'] ?? null;
		$this->assertIsArray( $sources );
		$this->assertGreaterThanOrEqual( 1, count( $sources ) );
		$this->assertSame( 'web', $sources[0]['source_name'] ?? '' );
	}
}
