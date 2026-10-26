<?php

declare(strict_types=1);

namespace S2J\Webinar\Tests\Integration;

use S2J\Webinar\Meta\OperationalMaterialMeta;
use S2J\Webinar\Meta\PostMetaRegistrar;
abstract class GatherPressEventTestCase extends IntegrationTestCase {

	protected function setUp(): void {
		parent::setUp();
		// WP_UnitTestCase tearDown clears registered meta between tests.
		PostMetaRegistrar::register_meta();
		OperationalMaterialMeta::register();
	}

	protected function event_post_type(): string {
		foreach ( get_post_types( array( 'public' => true ), 'names' ) as $type ) {
			if ( post_type_supports( $type, 'gatherpress-event-date' ) ) {
				return $type;
			}
		}

		$this->markTestSkipped( 'GatherPress event post type is not registered.' );
	}

	protected function create_event_post( string $title = 'Integration event' ): int {
		$post_type = $this->event_post_type();
		$post_id   = self::factory()->post->create(
			array(
				'post_type'   => $post_type,
				'post_title'  => $title,
				'post_status' => 'draft',
			)
		);
		$this->assertIsInt( $post_id );

		return $post_id;
	}
}
