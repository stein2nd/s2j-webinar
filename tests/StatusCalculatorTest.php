<?php

declare(strict_types=1);

namespace S2J\Webinar\Tests;

use PHPUnit\Framework\TestCase;
use S2J\Webinar\Record\StatusCalculator;
use S2J\Webinar\Tests\Support\WpFunctionStub;

final class StatusCalculatorTest extends TestCase {

	public function test_not_created_when_no_webinar_id(): void {
		$record = array(
			'webinar_id' => '',
			'last_error' => '',
		);
		$this->assertSame( 'not_created', StatusCalculator::compute( $record, 0 ) );
	}

	public function test_error_when_no_id_but_last_error(): void {
		$record = array(
			'webinar_id' => '',
			'last_error' => 'failed',
		);
		$this->assertSame( 'error', StatusCalculator::compute( $record, 0 ) );
	}

	public function test_dirty_when_id_present_without_last_sent(): void {
		$record = array(
			'webinar_id' => '99',
			'last_error' => '',
			'topic'      => 'Title',
		);
		$this->assertSame( 'dirty', StatusCalculator::compute( $record, 0 ) );
	}

	public function test_synced_when_body_matches_last_sent_meta(): void {
		$record = array(
			'webinar_id'       => '99',
			'last_error'       => '',
			'topic'            => 'Title',
			'agenda'           => '',
			'start_at'         => '2026-10-09T15:00:00+09:00',
			'timezone'         => 'Asia/Tokyo',
			'duration_minutes' => 60,
			'auto_recording'   => 'cloud',
			'approval_type'    => '0',
		);
		$this->assertSame( 'synced', StatusCalculator::compute( $record, 42 ) );
	}
}
