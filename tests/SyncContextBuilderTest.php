<?php

declare(strict_types=1);

namespace S2J\Webinar\Tests;

use PHPUnit\Framework\TestCase;
use S2J\Webinar\Sync\SyncContextBuilder;

final class SyncContextBuilderTest extends TestCase {

	public function test_for_sync_includes_webinar_title_and_flags(): void {
		$record = array(
			'topic'      => 'My Event',
			'webinar_id' => '123',
		);

		$context = SyncContextBuilder::for_sync( 0, $record, true, true );

		$this->assertSame( 'My Event', $context['webinar_title'] );
		$this->assertTrue( $context['intend_retry'] );
		$this->assertTrue( $context['intend_delete'] );
		$this->assertSame( array(), $context['previous_panelists'] );
		$this->assertNull( $context['survey_document'] );
	}

	public function test_read_ready_survey_document_returns_null_without_wp(): void {
		$this->assertNull( SyncContextBuilder::read_ready_survey_document( 0 ) );
	}
}
