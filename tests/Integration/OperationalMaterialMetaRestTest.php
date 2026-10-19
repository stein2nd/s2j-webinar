<?php

declare(strict_types=1);

namespace S2J\Webinar\Tests\Integration;

use WP_REST_Request;

final class OperationalMaterialMetaRestTest extends GatherPressEventTestCase {

	public function test_cover_art_meta_round_trips_via_rest(): void {
		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		$post_id    = $this->create_event_post();
		$post_type  = $this->event_post_type();
		$attachment = $this->create_image_attachment();

		$rest_base = get_post_type_object( $post_type )->rest_base ?? $post_type;

		$update = new WP_REST_Request( 'PUT', "/wp/v2/{$rest_base}/{$post_id}" );
		$update->set_body_params(
			array(
				'meta' => array(
					'_s2j_webinar_cover_art_id' => $attachment,
				),
			)
		);

		$response = rest_get_server()->dispatch( $update );
		$this->assertSame( 200, $response->get_status(), (string) wp_json_encode( $response->get_data() ) );
		$this->assertSame( (string) $attachment, (string) get_post_meta( $post_id, '_s2j_webinar_cover_art_id', true ) );

		$update->set_body_params(
			array(
				'meta' => array(
					'_s2j_webinar_cover_art_id' => 0,
				),
			)
		);
		rest_get_server()->dispatch( $update );
		$this->assertFalse( metadata_exists( 'post', $post_id, '_s2j_webinar_cover_art_id' ) );
	}

	public function test_invalid_cover_art_mime_is_rejected(): void {
		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		$post_id   = $this->create_event_post();
		$post_type = $this->event_post_type();
		$pdf_id    = $this->create_pdf_attachment();
		$image_id  = $this->create_image_attachment();

		update_post_meta( $post_id, '_s2j_webinar_cover_art_id', $image_id );

		$rest_base = get_post_type_object( $post_type )->rest_base ?? $post_type;
		$update    = new WP_REST_Request( 'PUT', "/wp/v2/{$rest_base}/{$post_id}" );
		$update->set_body_params(
			array(
				'meta' => array(
					'_s2j_webinar_cover_art_id' => $pdf_id,
				),
			)
		);

		rest_get_server()->dispatch( $update );
		$this->assertSame( (string) $image_id, (string) get_post_meta( $post_id, '_s2j_webinar_cover_art_id', true ) );
	}

	private function create_image_attachment(): int {
		$upload = wp_upload_bits( 'cover-art-test.png', null, $this->minimal_png_bytes() );
		$this->assertEmpty( $upload['error'] );

		$attachment_id = wp_insert_attachment(
			array(
				'post_mime_type' => 'image/png',
				'post_title'     => 'Cover art test',
				'post_content'   => '',
				'post_status'    => 'inherit',
			),
			$upload['file']
		);
		$this->assertIsInt( $attachment_id );

		return $attachment_id;
	}

	private function create_pdf_attachment(): int {
		$upload = wp_upload_bits( 'flyer-test.pdf', null, '%PDF-1.4 test' );
		$this->assertEmpty( $upload['error'] );

		$attachment_id = wp_insert_attachment(
			array(
				'post_mime_type' => 'application/pdf',
				'post_title'     => 'Flyer test',
				'post_content'   => '',
				'post_status'    => 'inherit',
			),
			$upload['file']
		);
		$this->assertIsInt( $attachment_id );

		return $attachment_id;
	}

	private function minimal_png_bytes(): string {
		return base64_decode(
			'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
			true
		) ?: '';
	}
}
