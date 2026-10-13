<?php
/**
 * GatherPress Settings sub-tab (near Tools / Alpha).
 *
 * @package S2J_Webinar
 */

declare(strict_types=1);

namespace S2J\Webinar\GatherPress;

use GatherPress\Core\Settings;
use GatherPress\Core\Utility;
use S2J\Webinar\Admin\SettingsPage;
use S2J\Webinar\Constants;

final class SettingsTab {

	private const SLUG = 'webinar_settings';

	public static function register(): void {
		if ( ! class_exists( '\GatherPress\Core\Settings' ) ) {
			return;
		}

		add_filter( 'gatherpress_sub_pages', array( self::class, 'add_sub_page' ) );
		add_action( 'gatherpress_settings_section', array( self::class, 'render_section' ), 9 );
	}

	/**
	 * @param array<string, array<string, mixed>> $sub_pages
	 * @return array<string, array<string, mixed>>
	 */
	public static function add_sub_page( array $sub_pages ): array {
		if ( ! SettingsPage::show_gatherpress_settings_tab() ) {
			return $sub_pages;
		}

		$sub_pages[ self::SLUG ] = array(
			'name'     => __( 'Webinar', 's2j-webinar' ),
			'priority' => PHP_INT_MAX - 2,
			'sections' => array(
				'zoom' => array(
					'name'        => __( 'Webinar connection', 's2j-webinar' ),
					'description' => __( 'Site-wide OAuth credentials, webhooks, and connection for event webinars.', 's2j-webinar' ),
				),
			),
		);
		return $sub_pages;
	}

	public static function render_section( string $page ): void {
		if ( Utility::unprefix_key( $page ) !== self::SLUG ) {
			return;
		}

		remove_action( 'gatherpress_settings_section', array( Settings::get_instance(), 'render_settings_form' ) );

		echo '<div class="wrap s2j-webinar-settings s2j-webinar-settings--gatherpress-tab">';
		SettingsPage::render_gatherpress_tab_inner();
		echo '</div>';
	}
}
