<?php

declare(strict_types=1);

namespace S2J\Webinar\Tests\Integration;

use WP_UnitTestCase;

/**
 * Local base for integration tests (PHPUnit 11+).
 */
abstract class IntegrationTestCase extends WP_UnitTestCase {

	/**
	 * Skip WP_UnitTestCase_Base::checkRequirements() docblock metadata (PHPUnit 11 deprecation).
	 *
	 * WordPress shim is deprecated/no-op; integration requirements are met in bootstrap.
	 */
	protected function checkRequirements(): void {
	}
}
