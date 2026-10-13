<?php

declare(strict_types=1);

namespace S2J\Webinar\Tests\Integration;

use S2J\Webinar\GatherPress\OnlineEventLinkWriter;
use S2J\Webinar\Record\RecordMetaWriter;

final class OnlineEventLinkIntegrationTest extends GatherPressEventTestCase {

	public function test_join_url_sync_updates_gatherpress_online_event_link(): void {
		$post_id = $this->create_event_post();

		RecordMetaWriter::write_after_sync(
			$post_id,
			array(
				'webinar_id'   => '100',
				'webinar_uuid' => 'uuid-100',
				'join_url'     => 'https://zoom.us/j/100',
				'status'       => 'synced',
				'last_error'   => '',
			)
		);

		$link = get_post_meta( $post_id, 'gatherpress_online_event_link', true );
		$this->assertSame( 'https://zoom.us/j/100', $link );

		OnlineEventLinkWriter::clear( $post_id );
		$this->assertSame( '', get_post_meta( $post_id, 'gatherpress_online_event_link', true ) );
	}
}
