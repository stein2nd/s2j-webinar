<?php

declare(strict_types=1);

namespace S2J\Webinar\Tests;

use PHPUnit\Framework\TestCase;
use S2J\Webinar\Rest\WebhookController;
use S2J\Webinar\Tests\Support\WpFunctionStub;

final class WebhookControllerTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		require_once __DIR__ . '/Support/wp-rest-stubs.php';
		WpFunctionStub::reset();
		WpFunctionStub::register();
		WpFunctionStub::seed(
			array(
				's2j_webinar_webhook_secret' => 'wh-secret',
			)
		);
	}

	protected function tearDown(): void {
		WpFunctionStub::reset();
		parent::tearDown();
	}

	public function test_rejects_invalid_signature(): void {
		$body    = wp_json_encode(
			array(
				'event'   => 'webinar.started',
				'payload' => array(
					'object' => array(
						'id' => '1',
					),
				),
			)
		);
		$request = new \WP_REST_Request();
		$request->set_body( is_string( $body ) ? $body : '' );
		$request->set_header( 'x-zm-request-timestamp', '1700000000' );
		$request->set_header( 'x-zm-signature', 'v0=deadbeef' );

		$response = WebhookController::handle( $request );

		$this->assertSame( 401, $response->get_status() );
	}

	public function test_accepts_valid_signature(): void {
		$body      = wp_json_encode(
			array(
				'event'   => 'webinar.started',
				'payload' => array(
					'object' => array(
						'id' => 'unknown-id',
					),
				),
			)
		);
		$timestamp = '1700000000';
		$message   = 'v0:' . $timestamp . ':' . $body;
		$signature = 'v0=' . hash_hmac( 'sha256', $message, 'wh-secret' );

		$request = new \WP_REST_Request();
		$request->set_body( is_string( $body ) ? $body : '' );
		$request->set_header( 'x-zm-request-timestamp', $timestamp );
		$request->set_header( 'x-zm-signature', $signature );

		$response = WebhookController::handle( $request );

		$this->assertSame( 200, $response->get_status() );
	}
}
