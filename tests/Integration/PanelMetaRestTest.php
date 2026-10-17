<?php

declare(strict_types=1);

namespace S2J\Webinar\Tests\Integration;

final class PanelMetaRestTest extends GatherPressEventTestCase {

	public function test_panel_meta_round_trips_via_rest(): void {
		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		$post_id   = $this->create_event_post();
		$post_type = $this->event_post_type();
		$registered = get_registered_meta_keys( 'post', $post_type );
		$this->assertArrayHasKey( '_s2j_webinar_auto_recording', $registered );

		$rest_base = get_post_type_object( $post_type )->rest_base ?? $post_type;

		$update = new \WP_REST_Request( 'PUT', "/wp/v2/{$rest_base}/{$post_id}" );
		$update->set_body_params(
			array(
				'meta' => array(
					'_s2j_webinar_auto_recording' => 'local',
					'_s2j_webinar_approval_type'  => '1',
					'_s2j_webinar_panelists'      => array(
						array(
							'name'  => 'Panelist',
							'email' => 'panelist@example.com',
						),
					),
				),
			)
		);

		$update_response = rest_get_server()->dispatch( $update );
		$this->assertSame( 200, $update_response->get_status(), (string) wp_json_encode( $update_response->get_data() ) );
		$this->assertSame( 'local', get_post_meta( $post_id, '_s2j_webinar_auto_recording', true ) );

		$get = new \WP_REST_Request( 'GET', "/wp/v2/{$rest_base}/{$post_id}" );
		$get->set_param( 'context', 'edit' );

		$data = rest_get_server()->dispatch( $get )->get_data();
		$this->assertSame( 'local', $data['meta']['_s2j_webinar_auto_recording'] ?? '' );
		$this->assertSame( '1', $data['meta']['_s2j_webinar_approval_type'] ?? '' );
		$this->assertIsArray( $data['meta']['_s2j_webinar_panelists'] ?? null );
		$this->assertSame( 'panelist@example.com', $data['meta']['_s2j_webinar_panelists'][0]['email'] ?? '' );
	}
}
