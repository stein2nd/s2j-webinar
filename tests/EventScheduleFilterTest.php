<?php

declare(strict_types=1);

namespace S2J\Webinar\Tests;

use PHPUnit\Framework\TestCase;
use S2J\Webinar\GatherPress\EventSchedule;

final class EventScheduleFilterTest extends TestCase {

	public function test_read_applies_filter_callback(): void {
		add_filter(
			's2j_webinar_gatherpress_schedule',
			static function ( array $schedule ): array {
				$schedule['start_at']         = '2026-10-09T15:00:00+09:00';
				$schedule['timezone']         = 'Asia/Tokyo';
				$schedule['duration_minutes'] = 90;
				return $schedule;
			}
		);

		$schedule = EventSchedule::read( 0 );
		$this->assertSame( '2026-10-09T15:00:00+09:00', $schedule['start_at'] );
		$this->assertSame( 'Asia/Tokyo', $schedule['timezone'] );
		$this->assertSame( 90, $schedule['duration_minutes'] );
	}
}
