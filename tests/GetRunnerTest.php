<?php

declare(strict_types=1);

namespace S2J\Webinar\Tests;

use PHPUnit\Framework\TestCase;
use S2J\Webinar\Http\WebinarHttpExecutor;
use S2J\Webinar\Sync\GetRunner;
use S2J\Webinar\Tests\Support\TestWpPost;
use S2J\Webinar\Tests\Support\WpFunctionStub;

final class GetRunnerTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		WpFunctionStub::reset();
		WpFunctionStub::register();
		WebinarHttpExecutor::$executor = null;
	}

	protected function tearDown(): void {
		WebinarHttpExecutor::$executor = null;
		WpFunctionStub::reset();
		WpFunctionStub::seedUnitTestDefaults();
		parent::tearDown();
	}

	public function test_open_returns_start_url_and_updates_join_url(): void {
		WpFunctionStub::seed(
			array(
				's2j_webinar_zoom_access_token' => 'unit-test-token',
			),
			array(
				5 => array(
					'_s2j_webinar_provider' => 'zoom',
					'_s2j_webinar_id'       => '555',
					'_s2j_webinar_status'   => 'synced',
				),
			)
		);

		WebinarHttpExecutor::$executor = static fn( array $material ): array => array(
			'status' => 200,
			'body'   => wp_json_encode(
				array(
					'start_url' => 'https://zoom.us/s/555',
					'join_url'  => 'https://zoom.us/j/555',
				)
			),
			'error'  => null,
		);

		$post   = TestWpPost::make( 5 );
		$result = GetRunner::open_in_zoom( $post );

		$this->assertTrue( $result['ok'] );
		$this->assertSame( 'https://zoom.us/s/555', $result['start_url'] ?? '' );
		$this->assertSame( 'https://zoom.us/j/555', WpFunctionStub::$post_meta[5]['_s2j_webinar_join_url'] ?? '' );
	}
}
