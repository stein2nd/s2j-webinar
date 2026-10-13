<?php

declare(strict_types=1);

namespace S2J\Webinar\Tests;

use PHPUnit\Framework\TestCase;
use S2J\Webinar\Security\ErrorRedaction;

final class ErrorRedactionTest extends TestCase {

	public function test_scrubs_bearer_token(): void {
		$input  = 'Failed Bearer eyJhbGciOiJIUzI1NiJ9.payload.sig';
		$output = ErrorRedaction::scrub( $input );
		$this->assertStringContainsString( 'Bearer [redacted]', $output );
		$this->assertStringNotContainsString( 'eyJhbGci', $output );
	}
}
