<?php

declare(strict_types=1);

namespace S2J\Webinar\Tests;

use PHPUnit\Framework\TestCase;
use S2J\Webinar\Http\WebinarHttpExecutor;
use S2J\Webinar\Sync\SyncRunner;
use S2J\Webinar\Tests\Support\TestWpPost;
use S2J\Webinar\Tests\Support\WpFunctionStub;

final class SyncRunnerTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		WpFunctionStub::reset();
		WpFunctionStub::register();
		WebinarHttpExecutor::$executor = null;

		add_filter(
			's2j_webinar_gatherpress_schedule',
			static fn( array $schedule ): array => array(
				'start_at'         => '2026-12-01T10:00:00+09:00',
				'timezone'         => 'Asia/Tokyo',
				'duration_minutes' => 60,
			),
			10,
			1
		);
	}

	protected function tearDown(): void {
		WebinarHttpExecutor::$executor = null;
		WpFunctionStub::reset();
		WpFunctionStub::seedUnitTestDefaults();
		parent::tearDown();
	}

	public function test_create_sync_persists_webinar_id_and_join_url(): void {
		$this->seed_connected_post( 7 );

		WebinarHttpExecutor::$executor = static function ( array $material ): array {
			if ( str_contains( $material['path'], '/users/me/webinars' ) ) {
				return array(
					'status' => 201,
					'body'   => wp_json_encode(
						array(
							'id'       => '9001',
							'uuid'     => 'uuid-9001',
							'join_url' => 'https://zoom.us/j/9001',
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
				'error'  => 'unexpected',
			);
		};

		$post   = $this->event_post( 7, 'Webinar topic' );
		$result = SyncRunner::sync_post( $post );

		$this->assertTrue( $result['ok'] );
		$this->assertSame( '9001', WpFunctionStub::$post_meta[7]['_s2j_webinar_id'] ?? '' );
		$this->assertSame( 'https://zoom.us/j/9001', WpFunctionStub::$post_meta[7]['_s2j_webinar_join_url'] ?? '' );
		$this->assertSame( 'synced', WpFunctionStub::$post_meta[7]['_s2j_webinar_status'] ?? '' );
	}

	public function test_step_failure_keeps_last_sent_from_create(): void {
		$this->seed_connected_post( 8 );

		WebinarHttpExecutor::$executor = static function ( array $material ): array {
			if ( str_contains( $material['path'], '/users/me/webinars' ) ) {
				return array(
					'status' => 201,
					'body'   => wp_json_encode(
						array(
							'id'       => '9002',
							'uuid'     => 'uuid-9002',
							'join_url' => 'https://zoom.us/j/9002',
						)
					),
					'error'  => null,
				);
			}
			if ( str_contains( $material['path'], '/panelists' ) ) {
				return array(
					'status' => 400,
					'body'   => wp_json_encode( array( 'message' => 'panelists failed' ) ),
					'error'  => null,
				);
			}

			return array(
				'status' => 500,
				'body'   => null,
				'error'  => 'unexpected',
			);
		};

		$post   = $this->event_post( 8, 'Webinar topic' );
		$result = SyncRunner::sync_post( $post );

		$this->assertFalse( $result['ok'] );
		$snapshot = WpFunctionStub::$post_meta[8]['_s2j_webinar_last_sent'] ?? null;
		$this->assertIsArray( $snapshot );
		$this->assertSame( 'Webinar topic', $snapshot['topic'] ?? '' );
		$this->assertSame( 'Asia/Tokyo', $snapshot['timezone'] ?? '' );
	}

	private function seed_connected_post( int $post_id ): void {
		WpFunctionStub::seed(
			array(
				's2j_webinar_zoom_access_token' => 'unit-test-token',
			),
			array(
				$post_id => array(
					'_s2j_webinar_provider'      => 'zoom',
					'_s2j_webinar_auto_recording' => 'cloud',
					'_s2j_webinar_approval_type'  => '0',
					'_s2j_webinar_panelists'      => array(
						array(
							'name'  => 'Host',
							'email' => 'host@example.com',
						),
					),
				),
			)
		);
	}

	private function event_post( int $post_id, string $title ): \WP_Post {
		return TestWpPost::make( $post_id, $title );
	}
}
