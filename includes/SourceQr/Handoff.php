<?php

/**
 * Source QR panel handoff (editor hooks; UI in Gutenberg).
 *
 * @package S2J_Webinar
 */

declare(strict_types=1);

namespace S2J\Webinar\SourceQr;

final class Handoff
{
    /** @wordpress/hooks action name — Source QR registers the listener. */
    public const OPEN_HOOK = 's2j_webinar.open_source_qr';

    public static function is_companion_active(): bool
    {
        if (defined('S2J_WEBINAR_SOURCE_QR_VERSION')) {
            return true;
        }

        if (! function_exists('is_plugin_active')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        return is_plugin_active('s2j-webinar-source-qr/s2j-webinar-source-qr.php');
    }
}
