<?php

/**
 * Enqueue Vite-built admin and block editor bundles.
 *
 * @package S2J_Webinar
 */

declare(strict_types=1);

namespace S2J\Webinar\Admin;

use S2J\Webinar\Constants;
use S2J\Webinar\OAuth\TokenStore;
use S2J\Webinar\SourceQr\Handoff;

final class AssetLoader
{
    public static function register(): void
    {
        add_action('admin_enqueue_scripts', array( self::class, 'enqueue_admin' ));
        add_action('enqueue_block_editor_assets', array( self::class, 'enqueue_block_editor' ));
    }

    public static function enqueue_admin(string $hook): void
    {
        // Admin bundle reserved for future Settings → S2J Webinar UI; credentials UI is PHP-only.
        unset($hook);
    }

    public static function enqueue_block_editor(): void
    {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if ($screen === null || $screen->post_type === null) {
            return;
        }
        if (! post_type_supports($screen->post_type, 'gatherpress-event-date')) {
            return;
        }

        self::enqueue_bundle(
            'gutenberg',
            's2j-webinar-gutenberg',
            array(
                'connected'     => TokenStore::is_connected(),
                'restRoot'      => esc_url_raw(rest_url()),
                'nonce'         => wp_create_nonce('wp_rest'),
                'providerSlug'  => SettingsPage::webinar_provider_slug(),
                'providerLabel' => SettingsPage::webinar_provider_label(),
                'sourceQrActive' => Handoff::is_companion_active(),
                'sourceQrOpenHook' => Handoff::OPEN_HOOK,
            )
        );
    }

    /**
     * @param array<string, mixed> $config
     */
    private static function enqueue_bundle(string $target, string $handle, array $config): void
    {
        $script_rel = "dist/js/s2j-webinar-{$target}.js";
        $style_rel  = "dist/css/s2j-webinar-{$target}.css";
        $script_abs = S2J_WEBINAR_PLUGIN_DIR . $script_rel;
        if (! is_readable($script_abs)) {
            return;
        }

        $version = (string) filemtime($script_abs);
        wp_enqueue_script(
            $handle,
            S2J_WEBINAR_PLUGIN_URL . $script_rel,
            array(
                'wp-plugins',
                'wp-edit-post',
                'wp-editor',
                'wp-element',
                'wp-i18n',
                'wp-components',
                'wp-data',
                'wp-core-data',
                'wp-api-fetch',
                'wp-hooks',
            ),
            $version,
            true
        );

        $style_abs = S2J_WEBINAR_PLUGIN_DIR . $style_rel;
        if (is_readable($style_abs)) {
            wp_enqueue_style(
                $handle,
                S2J_WEBINAR_PLUGIN_URL . $style_rel,
                array(),
                (string) filemtime($style_abs)
            );
        }

        wp_localize_script($handle, 's2jWebinarConfig', $config);

        wp_set_script_translations(
            $handle,
            's2j-webinar',
            S2J_WEBINAR_PLUGIN_DIR . 'languages'
        );
    }
}
