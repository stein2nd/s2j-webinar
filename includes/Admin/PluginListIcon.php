<?php

/**
 * Plugin list icon (seminar / webinar, not generic video).
 *
 * @package S2J_Webinar
 */

declare(strict_types=1);

namespace S2J\Webinar\Admin;

final class PluginListIcon
{
    public static function register(): void
    {
        add_action('admin_enqueue_scripts', array( self::class, 'enqueue_plugins_screen' ));
        add_filter('plugin_action_links_' . S2J_WEBINAR_PLUGIN_BASENAME, array( self::class, 'action_links' ));
    }

    public static function enqueue_plugins_screen(string $hook): void
    {
        if ($hook !== 'plugins.php') {
            return;
        }

        $icon_url = S2J_WEBINAR_PLUGIN_URL . 'assets/icon.svg';
        $css      = sprintf(
            '#the-list tr[data-plugin="%s"] .plugin-icon{background-image:url(%s);background-size:contain;background-repeat:no-repeat;background-position:center}',
            esc_attr(S2J_WEBINAR_PLUGIN_BASENAME),
            esc_url($icon_url)
        );
        wp_add_inline_style('dashicons', $css);
    }

    /**
     * @param list<string> $links
     * @return list<string>
     */
    public static function action_links(array $links): array
    {
        $url = admin_url('options-general.php?page=' . \S2J\Webinar\Constants::SETTINGS_PAGE_SLUG);
        array_unshift(
            $links,
            sprintf(
                '<a href="%s">%s</a>',
                esc_url($url),
                esc_html__('Settings', 's2j-webinar')
            )
        );
        return $links;
    }
}
