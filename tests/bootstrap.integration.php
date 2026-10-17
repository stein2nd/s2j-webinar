<?php

declare(strict_types=1);

/**
 * Bootstrap for WordPress integration tests (local MySQL or optional wp-env).
 *
 * @package S2J_Webinar
 */

$plugin_dir = dirname( __DIR__ );

$polyfills = $plugin_dir . '/vendor/yoast/phpunit-polyfills/phpunitpolyfills-autoload.php';
if ( file_exists( $polyfills ) ) {
	require_once $polyfills;
}

$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! is_string( $_tests_dir ) || $_tests_dir === '' ) {
	$_tests_dir = $plugin_dir . '/tests/.wordpress-tests-lib';
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	echo 'Could not find ' . $_tests_dir . '/includes/functions.php.' . PHP_EOL;
	echo 'Local (no Docker): copy .env.tests.example to .env.tests, then npm run test:unit:php:local' . PHP_EOL;
	echo 'Or: bash bin/install-wp-tests.sh <db> <user> <pass> [host]' . PHP_EOL;
	echo 'Optional wp-env: npm run test:unit:php' . PHP_EOL;
	exit( 1 );
}

require_once $_tests_dir . '/includes/functions.php';

tests_add_filter(
	'muplugins_loaded',
	static function (): void {
		$gp_dir = getenv( 'WP_TESTS_GATHERPRESS_DIR' );
		if ( is_string( $gp_dir ) && $gp_dir !== '' ) {
			$main = rtrim( $gp_dir, '/' ) . '/gatherpress.php';
			if ( is_readable( $main ) ) {
				require $main;
				$alpha = dirname( rtrim( $gp_dir, '/' ) ) . '/gatherpress-alpha/gatherpress-alpha.php';
				if ( is_readable( $alpha ) ) {
					require $alpha;
				}
			}
		}

		require dirname( __DIR__ ) . '/s2j-webinar.php';
	}
);

require $_tests_dir . '/includes/bootstrap.php';

require_once $plugin_dir . '/vendor/autoload.php';
