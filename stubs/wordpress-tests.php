<?php
/**
 * IDE stubs for wordpress-develop test library (never load at runtime).
 *
 * @see https://github.com/WordPress/wordpress-develop/blob/trunk/tests/phpunit/includes/functions.php
 *
 * @package S2J_Webinar
 */

declare(strict_types=1);

namespace {
	if ( ! function_exists( 'tests_add_filter' ) ) {
		/**
		 * @param callable(mixed ...): mixed $callback
		 */
		function tests_add_filter(
			string $hook,
			callable $callback,
			int $priority = 10,
			int $accepted_args = 1
		): bool {
			unset( $hook, $callback, $priority, $accepted_args );
			return true;
		}
	}

	if ( ! class_exists( 'WP_UnitTestCase', false ) ) {
		/**
		 * @see https://github.com/WordPress/wordpress-develop/blob/trunk/tests/phpunit/includes/abstract-testcase.php
		 */
		class WP_UnitTestCase extends \PHPUnit\Framework\TestCase {
		}
	}
}
