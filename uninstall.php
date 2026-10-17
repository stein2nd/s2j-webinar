<?php
/**
 * Uninstall S2J Webinar — owned meta and site options only.
 *
 * @package S2J_Webinar
 */

declare(strict_types=1);

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

require_once __DIR__ . '/includes/Constants.php';

use S2J\Webinar\Constants;

global $wpdb;

foreach ( Constants::owned_post_meta_keys() as $meta_key ) {
	// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.SlowDBQuery -- uninstall: bulk delete owned meta keys.
	$wpdb->delete(
		$wpdb->postmeta,
		array( 'meta_key' => $meta_key ),
		array( '%s' )
	);
	// phpcs:enable
}

foreach ( Constants::owned_option_keys() as $option_key ) {
	delete_option( $option_key );
}
