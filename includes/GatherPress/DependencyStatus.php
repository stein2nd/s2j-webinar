<?php

/**
 * Whether GatherPress (and optional alpha) is ready for S2J Webinar.
 *
 * @package S2J_Webinar
 */

declare(strict_types=1);

namespace S2J\Webinar\GatherPress;

use S2J\Webinar\Constants;

final class DependencyStatus
{
    public static function is_gatherpress_active(): bool
    {
        if (! function_exists('is_plugin_active')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        return is_plugin_active('gatherpress/gatherpress.php');
    }

    public static function is_gatherpress_alpha_active(): bool
    {
        if (! function_exists('is_plugin_active')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        return is_plugin_active('gatherpress-alpha/gatherpress-alpha.php');
    }

    public static function has_event_post_type(): bool
    {
        foreach (get_post_types(array( 'public' => true ), 'names') as $type) {
            if (post_type_supports($type, 'gatherpress-event-date')) {
                return true;
            }
        }
        return false;
    }

    public static function is_ready(): bool
    {
        return self::is_gatherpress_active() && self::has_event_post_type();
    }

    /**
     * @return list<string>
     */
    public static function missing_requirements(): array
    {
        $missing = array();
        if (! self::is_gatherpress_active()) {
            $missing[] = 'gatherpress';
        }
        if (self::is_gatherpress_active() && ! self::has_event_post_type()) {
            $missing[] = 'event_post_type';
        }
        return $missing;
    }

    public static function settings_url(): string
    {
        return admin_url('options-general.php?page=' . Constants::SETTINGS_PAGE_SLUG);
    }

    public static function gatherpress_settings_url(): string
    {
        if (! class_exists('\GatherPress\Core\Utility')) {
            return admin_url('edit.php?post_type=gatherpress_event');
        }
        return admin_url(
            'edit.php?post_type=gatherpress_event&page=' . \GatherPress\Core\Utility::prefix_key('webinar_settings')
        );
    }
}
