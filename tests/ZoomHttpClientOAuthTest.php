<?php

declare(strict_types=1);

namespace S2J\Webinar\Tests;

use PHPUnit\Framework\TestCase;
use S2J\Webinar\Http\ZoomHttpClient;
use S2J\Webinar\Tests\Support\WpFunctionStub;

final class ZoomHttpClientOAuthTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		WpFunctionStub::reset();
		WpFunctionStub::register();
		WpFunctionStub::seed(
			array(
				's2j_webinar_zoom_client_id'     => 'client-id',
				's2j_webinar_zoom_client_secret' => 'client-secret',
				's2j_webinar_zoom_refresh_token' => 'refresh-token-value',
			)
		);
	}

	protected function tearDown(): void {
		WpFunctionStub::reset();
		parent::tearDown();
	}

	public function test_refresh_sends_basic_auth_and_form_urlencoded_body(): void {
		/** @var array<string, mixed>|null $captured_args */
		$captured_args = null;

		WpFunctionStub::$remote_request_handler = static function ( string $url, array $args ) use ( &$captured_args ): array {
			$captured_args = $args;
			return array(
				'response' => array( 'code' => 200 ),
				'body'     => wp_json_encode(
					array(
						'access_token'  => 'new-access',
						'refresh_token'   => 'new-refresh',
						'expires_in'      => 3600,
					)
				),
			);
		};

		$result = ZoomHttpClient::refresh_access_token();

		$this->assertSame( 200, $result['status'] );
		$this->assertIsArray( $captured_args );
		$this->assertSame( 'POST', $captured_args['method'] );
		$this->assertSame(
			'Basic ' . base64_encode( 'client-id:client-secret' ),
			$captured_args['headers']['Authorization']
		);
		$this->assertSame( 'application/x-www-form-urlencoded', $captured_args['headers']['Content-Type'] );
		$this->assertIsString( $captured_args['body'] );
		parse_str( $captured_args['body'], $parsed );
		$this->assertSame( 'refresh_token', $parsed['grant_type'] ?? '' );
		$this->assertSame( 'refresh-token-value', $parsed['refresh_token'] ?? '' );
	}
}
