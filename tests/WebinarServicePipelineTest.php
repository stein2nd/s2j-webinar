<?php

declare(strict_types=1);

namespace S2J\Webinar\Tests;

use PHPUnit\Framework\TestCase;
use S2J\Webinar\ServiceBridge;
use S2J\Webinar\Sync\SyncContextBuilder;
use S2J\WebinarService\Core\Deficiency;

use function S2J\WebinarService\plan_webinar_operations;

/**
 * s2j/webinar-service via ServiceBridge — no Zoom HTTP or Marketplace OAuth.
 */
final class WebinarServicePipelineTest extends TestCase {

	public function test_webinar_service_is_available(): void {
		$this->assertTrue( ServiceBridge::is_available() );
	}

	public function test_validate_record_reports_topic_empty(): void {
		$result = ServiceBridge::validate_record(
			array(
				'topic' => '',
			)
		);

		$this->assertContains( Deficiency::TOPIC_EMPTY, $result['deficiencies'] );
	}

	public function test_validate_record_accepts_minimal_create_ready_record(): void {
		$result = ServiceBridge::validate_record( $this->minimalValidRecord() );

		$this->assertSame( array(), $result['deficiencies'] );
		$this->assertSame( 'Integration topic', $result['record']['topic'] ?? '' );
	}

	public function test_plan_and_build_request_create_without_http(): void {
		$validated = ServiceBridge::validate_record( $this->minimalValidRecord() );
		$this->assertSame( array(), $validated['deficiencies'] );

		$context = SyncContextBuilder::for_sync( 0, $validated['record'], false, false );
		$steps   = plan_webinar_operations( $validated['record'], $context );
		$this->assertNotEmpty( $steps );

		$create_step = null;
		foreach ( $steps as $step ) {
			if ( ( $step['op'] ?? '' ) === 'create' ) {
				$create_step = $step;
				break;
			}
		}
		$this->assertIsArray( $create_step );

		$built = ServiceBridge::build_request( 'create', $validated['record'], $create_step );
		$this->assertSame( array(), $built['deficiencies'] ?? array() );
		$this->assertIsArray( $built['material'] ?? null );
		$this->assertStringContainsString( 'webinars', (string) ( $built['material']['path'] ?? '' ) );
		$this->assertSame( 'POST', $built['material']['method'] ?? '' );
	}

	/**
	 * @return array<string, mixed>
	 */
	private function minimalValidRecord(): array {
		return array(
			'topic'             => 'Integration topic',
			'agenda'            => '',
			'start_at'          => '2026-12-01T10:00:00+09:00',
			'timezone'          => 'Asia/Tokyo',
			'duration_minutes'  => 60,
			'auto_recording'    => 'cloud',
			'approval_type'     => 0,
			'panelists'         => array(
				array(
					'name'  => 'Host',
					'email' => 'host@example.com',
				),
			),
			'provider'          => 'zoom',
			'status'            => 'not_created',
			'webinar_id'        => '',
			'join_url'          => '',
		);
	}
}
