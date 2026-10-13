<?php
/**
 * S2J Webinar
 *
 * @package S2J_Webinar
 * @author Koutarou ISHIKAWA
 * @copyright 2026 Koutarou ISHIKAWA
 * @license GPL-3.0-or-later
 *
 * Plugin Name: S2J Webinar
 * Plugin URI: https://github.com/stein2nd/s2j-webinar
 * Description: Create and sync Webinars from GatherPress event editing screens.
 * Version: 0.0.1
 * Author: Koutarou ISHIKAWA
 * Author URI: https://stein2nd.wordpress.com
 * License: GPL-3.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: s2j-webinar
 * Domain Path: /languages
 * Requires at least: 6.3
 * Requires PHP: 8.2
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'S2J_WEBINAR_VERSION', '0.0.1' );
define( 'S2J_WEBINAR_PLUGIN_FILE', __FILE__ );
define( 'S2J_WEBINAR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'S2J_WEBINAR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'S2J_WEBINAR_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

$autoload = S2J_WEBINAR_PLUGIN_DIR . 'vendor/autoload.php';
if ( is_readable( $autoload ) ) {
	require_once $autoload;
}

require_once S2J_WEBINAR_PLUGIN_DIR . 'includes/Plugin.php';

S2J\Webinar\Plugin::init();
