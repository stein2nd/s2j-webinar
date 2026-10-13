<?php

declare(strict_types=1);

/**
 * Bootstrap for wp-env integration tests (run via npm run test:unit:php).
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = '/tmp/wordpress-tests-lib';
}

require_once $_tests_dir . '/includes/functions.php';

if ( ! function_exists( 'tests_add_filter' ) ) {
	require_once dirname( __DIR__ ) . '/stubs/wordpress-tests.php';
}

tests_add_filter(
	'muplugins_loaded',
	static function (): void {
		require dirname( __DIR__ ) . '/s2j-webinar.php';
	}
);

require $_tests_dir . '/includes/bootstrap.php';

require_once dirname( __DIR__ ) . '/vendor/autoload.php';
