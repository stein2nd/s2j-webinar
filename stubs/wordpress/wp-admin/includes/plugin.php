<?php
/**
 * PHPStan bootstrap stub for wp-admin/includes/plugin.php.
 *
 * @package S2J_Webinar
 */

if ( ! function_exists( 'is_plugin_active' ) ) {
	/**
	 * @param string $plugin Basename path.
	 */
	function is_plugin_active( $plugin ) {
		unset( $plugin );
		return false;
	}
}
