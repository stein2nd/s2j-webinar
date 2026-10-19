<?php

/**
 * Option and meta key names (docs/data_dictionary.md).
 *
 * @package S2J_Webinar
 */

declare(strict_types=1);

namespace S2J\Webinar;

final class Constants
{
    /** Must match the literal second argument of __() / esc_html__() etc. (WP-CLI i18n extraction). */
    public const TEXT_DOMAIN = 's2j-webinar';

    public const REST_NAMESPACE = 's2j-webinar/v1';

    public const SETTINGS_PAGE_SLUG = 's2j-webinar';

    public const OAUTH_CALLBACK_ACTION = 's2j_webinar_oauth_callback';

    /** GatherPress → Webinar tab save (avoids options.php access check on POST). */
    public const ADMIN_POST_SAVE_GP_SETTINGS = 's2j_webinar_save_gatherpress_settings';

    public const DEFAULT_PROVIDER = 'zoom';

    public const OPTION_WEBINAR_PROVIDER = 's2j_webinar_provider';

    public const OPTION_SHOW_GP_SETTINGS_TAB = 's2j_webinar_show_gatherpress_settings_tab';

    public const ZOOM_API_BASE = 'https://api.zoom.us';

    /** Default GitHub URL for docs/provider_setup_guides.md (filterable in admin UI). */
    public const DOCS_PROVIDER_SETUP_GUIDE_URL = 'https://github.com/stein2nd/s2j-webinar/blob/main/docs/provider_setup_guides.md';

    /**
     * @return list<string>
     */
    public static function panel_post_meta_keys(): array
    {
        return array(
            '_s2j_webinar_provider',
            '_s2j_webinar_auto_recording',
            '_s2j_webinar_approval_type',
            '_s2j_webinar_panelists',
            '_s2j_webinar_cover_art_id',
            '_s2j_webinar_flyer_id',
        );
    }

    /**
     * @return list<string>
     */
    public static function server_post_meta_keys(): array
    {
        return array(
            '_s2j_webinar_id',
            '_s2j_webinar_uuid',
            '_s2j_webinar_join_url',
            '_s2j_webinar_status',
            '_s2j_webinar_last_error',
            '_s2j_webinar_last_sent',
            '_s2j_webinar_last_panelists',
            '_s2j_webinar_session',
        );
    }

    /**
     * @return list<string>
     */
    public static function owned_post_meta_keys(): array
    {
        return array_merge(
            self::panel_post_meta_keys(),
            self::server_post_meta_keys()
        );
    }

    public static function owned_option_keys(): array
    {
        return array(
            self::OPTION_SHOW_GP_SETTINGS_TAB,
            self::OPTION_WEBINAR_PROVIDER,
            's2j_webinar_zoom_client_id',
            's2j_webinar_zoom_client_secret',
            's2j_webinar_zoom_access_token',
            's2j_webinar_zoom_refresh_token',
            's2j_webinar_zoom_token_expires_at',
            's2j_webinar_zoom_account_email',
            's2j_webinar_webhook_secret',
        );
    }

    public static function is_gatherpress_event(int $post_id): bool
    {
        return post_type_supports(get_post_type($post_id), 'gatherpress-event-date');
    }
}
