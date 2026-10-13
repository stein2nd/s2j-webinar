<?php

declare(strict_types=1);

namespace S2J\Webinar\Tests;

use PHPUnit\Framework\TestCase;
use S2J\Webinar\Http\WebinarHttpExecutor;
use S2J\Webinar\Sync\DeleteRunner;
use S2J\Webinar\Tests\Support\TestWpPost;
use S2J\Webinar\Tests\Support\WpFunctionStub;

final class DeleteRunnerTest extends TestCase {

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

	public function test_delete_clears_webinar_meta_and_public_link(): void {
		WpFunctionStub::seed(
			array(
				's2j_webinar_zoom_access_token' => 'unit-test-token',
			),
			array(
				9 => array(
					'_s2j_webinar_provider'      => 'zoom',
					'_s2j_webinar_id'            => '777',
					'_s2j_webinar_uuid'          => 'uuid-777',
					'_s2j_webinar_join_url'      => 'https://zoom.us/j/777',
					'_s2j_webinar_status'        => 'synced',
					'_s2j_webinar_auto_recording' => 'cloud',
					'_s2j_webinar_approval_type'  => '0',
					'_s2j_webinar_panelists'      => array(
						array(
							'name'  => 'Host',
							'email' => 'host@example.com',
						),
					),
					'gatherpress_online_event_link' => 'https://zoom.us/j/777',
					'_s2j_webinar_last_sent'        => array( 'topic' => 'Title' ),
					'_s2j_webinar_last_panelists'   => array(),
				),
			)
		);

		WebinarHttpExecutor::$executor = static fn( array $material ): array => array(
			'status' => 204,
			'body'   => '',
			'error'  => null,
		);

		$post   = TestWpPost::make( 9, 'Title' );
		$result = DeleteRunner::delete_from_zoom( $post );

		$this->assertTrue( $result['ok'] );
		$this->assertSame( '', WpFunctionStub::$post_meta[9]['_s2j_webinar_id'] ?? 'missing' );
		$this->assertSame( 'not_created', WpFunctionStub::$post_meta[9]['_s2j_webinar_status'] ?? '' );
		$this->assertArrayNotHasKey( '_s2j_webinar_last_sent', WpFunctionStub::$post_meta[9] ?? array() );
		$this->assertArrayNotHasKey( 'gatherpress_online_event_link', WpFunctionStub::$post_meta[9] ?? array() );
	}
}
