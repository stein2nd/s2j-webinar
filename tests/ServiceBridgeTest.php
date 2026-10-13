<?php

declare(strict_types=1);

namespace S2J\Webinar\Tests;

use PHPUnit\Framework\TestCase;
use S2J\Webinar\ServiceBridge;

final class ServiceBridgeTest extends TestCase {

	public function test_webinar_service_is_available(): void {
		$this->assertTrue( ServiceBridge::is_available() );
	}
}
