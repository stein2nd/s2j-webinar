<?php

declare(strict_types=1);

namespace S2J\Webinar\Tests\Integration;

use S2J\Webinar\GatherPress\EventSchedule;
use S2J\Webinar\Record\RecordAssembler;

final class EventScheduleIntegrationTest extends GatherPressEventTestCase {

	public function test_gatherpress_datetime_yields_non_empty_start_at(): void {
		$post_id = $this->create_event_post();

		update_post_meta( $post_id, 'gatherpress_datetime_start', '2026-12-15 14:30:00' );
		update_post_meta( $post_id, 'gatherpress_datetime_end', '2026-12-15 15:15:00' );
		update_post_meta( $post_id, 'gatherpress_timezone', 'Asia/Tokyo' );

		$schedule = EventSchedule::read( $post_id );
		$this->assertNotSame( '', $schedule['start_at'] );
		$this->assertSame( 'Asia/Tokyo', $schedule['timezone'] );
		$this->assertSame( 45, $schedule['duration_minutes'] );

		$post   = get_post( $post_id );
		$record = RecordAssembler::from_post( $post );
		$this->assertSame( $schedule['start_at'], $record['start_at'] );
	}
}
