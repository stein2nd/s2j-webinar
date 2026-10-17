<?php
/**
 * PHPStan bootstrap: plugin constants defined in the main plugin file at runtime.
 *
 * @package S2J_Webinar
 */

define( 'S2J_WEBINAR_VERSION', '0.0.1' );
define( 'S2J_WEBINAR_PLUGIN_FILE', __DIR__ . '/s2j-webinar.php' );
define( 'S2J_WEBINAR_PLUGIN_DIR', __DIR__ . '/' );
define( 'S2J_WEBINAR_PLUGIN_URL', 'http://example.test/wp-content/plugins/s2j-webinar/' );
define( 'S2J_WEBINAR_PLUGIN_BASENAME', 's2j-webinar/s2j-webinar.php' );

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/stubs/wordpress/' );
}
