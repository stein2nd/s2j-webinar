<?php

/**
 * Plugin bootstrap.
 *
 * @package S2J_Webinar
 */

declare(strict_types=1);

namespace S2J\Webinar;

use S2J\Webinar\Admin\AssetLoader;
use S2J\Webinar\Admin\PluginListIcon;
use S2J\Webinar\Admin\SettingsPage;
use S2J\Webinar\GatherPress\SettingsTab;
use S2J\Webinar\Meta\PanelMetaSaver;
use S2J\Webinar\Meta\PostMetaRegistrar;
use S2J\Webinar\OAuth\OAuthFlow;
use S2J\Webinar\Rest\EventActionsController;
use S2J\Webinar\Rest\SyncController;
use S2J\Webinar\Rest\WebhookController;

final class Plugin
{
    public static function init(): void
    {
        add_action('plugins_loaded', array( self::class, 'on_plugins_loaded' ));
        if (did_action('plugins_loaded')) {
            self::on_plugins_loaded();
        }
    }

    public static function on_plugins_loaded(): void
    {
        load_plugin_textdomain(
            Constants::TEXT_DOMAIN,
            false,
            dirname(S2J_WEBINAR_PLUGIN_BASENAME) . '/languages'
        );

        if (! ServiceBridge::is_available()) {
            add_action('admin_notices', array( self::class, 'render_missing_vendor_notice' ));
            return;
        }

        PostMetaRegistrar::register();
        PanelMetaSaver::register();
        SettingsPage::register();
        PluginListIcon::register();
        SettingsTab::register();
        OAuthFlow::register();
        SyncController::register();
        EventActionsController::register();
        WebhookController::register();
        AssetLoader::register();
    }

    public static function render_missing_vendor_notice(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }
        echo '<div class="notice notice-error"><p>';
        echo esc_html__(
            'S2J Webinar: Composer dependencies are missing. Run composer install in the plugin directory.',
            's2j-webinar'
        );
        echo '</p></div>';
    }
}
