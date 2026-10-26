<?php

/**
 * Optional S2J companion plugins (Survey, Source QR).
 *
 * @package S2J_Webinar
 */

declare(strict_types=1);

namespace S2J\Webinar\Companion;

final class CompanionStatus
{
    public const SOURCE_QR_PLUGIN = 's2j-webinar-source-qr/s2j-webinar-source-qr.php';

    public const SURVEY_PLUGIN = 's2j-webinar-survey/s2j-webinar-survey.php';

    public const SOURCE_QR_INSTALL_URL = 'https://github.com/stein2nd/s2j-webinar-source-qr#installation';

    public const SURVEY_INSTALL_URL = 'https://github.com/stein2nd/s2j-webinar-survey#installation';

    public static function is_source_qr_active(): bool
    {
        if (defined('S2J_WEBINAR_SOURCE_QR_VERSION')) {
            return true;
        }

        return self::is_active(self::SOURCE_QR_PLUGIN);
    }

    public static function is_survey_active(): bool
    {
        if (defined('S2J_WEBINAR_SURVEY_VERSION')) {
            return true;
        }

        return self::is_active(self::SURVEY_PLUGIN);
    }

    public static function is_installed(string $plugin_file): bool
    {
        self::ensure_plugin_api();

        $plugins = get_plugins();

        return isset($plugins[ $plugin_file ]);
    }

    public static function is_active(string $plugin_file): bool
    {
        self::ensure_plugin_api();

        return is_plugin_active($plugin_file);
    }

    public static function plugins_admin_url(): string
    {
        return admin_url('plugins.php');
    }

    public static function activate_url(string $plugin_file): string
    {
        if (! current_user_can('activate_plugins')) {
            return '';
        }

        self::ensure_plugin_api();

        return wp_nonce_url(
            admin_url('plugins.php?action=activate&plugin=' . $plugin_file),
            'activate-plugin_' . $plugin_file
        );
    }

    /**
     * @return list<array{slug: string, label: string, installed: bool, active: bool, install_url: string, activate_url: string}>
     */
    public static function companion_rows(): array
    {
        $rows = array(
            array(
                'slug'         => 'source-qr',
                'label'        => __('S2J Webinar Source QR', 's2j-webinar'),
                'plugin_file'  => self::SOURCE_QR_PLUGIN,
                'install_url'  => self::SOURCE_QR_INSTALL_URL,
            ),
            array(
                'slug'         => 'survey',
                'label'        => __('S2J Webinar Survey', 's2j-webinar'),
                'plugin_file'  => self::SURVEY_PLUGIN,
                'install_url'  => self::SURVEY_INSTALL_URL,
            ),
        );

        $out = array();
        foreach ($rows as $row) {
            $file   = $row['plugin_file'];
            $active = 'source-qr' === $row['slug']
                ? self::is_source_qr_active()
                : self::is_survey_active();
            $installed = self::is_installed($file) || $active;

            $out[] = array(
                'slug'          => $row['slug'],
                'label'         => $row['label'],
                'installed'     => $installed,
                'active'        => $active,
                'install_url'   => $row['install_url'],
                'activate_url'  => $installed && ! $active ? self::activate_url($file) : '',
            );
        }

        return $out;
    }

    public static function all_companions_active(): bool
    {
        return self::is_source_qr_active() && self::is_survey_active();
    }

    private static function ensure_plugin_api(): void
    {
        if (! function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
    }
}
