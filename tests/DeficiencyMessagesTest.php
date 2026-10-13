<?php

declare(strict_types=1);

namespace S2J\Webinar\Tests;

use PHPUnit\Framework\TestCase;
use S2J\Webinar\Messages\DeficiencyMessages;

final class DeficiencyMessagesTest extends TestCase {

	public function test_known_code_returns_message(): void {
		$this->assertSame(
			'Event title is required.',
			DeficiencyMessages::translate_code( 'topic_empty' )
		);
	}

	public function test_unknown_code_returns_fallback(): void {
		$this->assertStringContainsString(
			'Could not sync',
			DeficiencyMessages::translate_code( 'unknown_code_xyz' )
		);
	}
}
