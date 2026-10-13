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
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery
	$wpdb->delete(
		$wpdb->postmeta,
		array( 'meta_key' => $meta_key ),
		array( '%s' )
	);
}

foreach ( Constants::owned_option_keys() as $option_key ) {
	delete_option( $option_key );
}
