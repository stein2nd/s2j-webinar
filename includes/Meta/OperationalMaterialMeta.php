<?php

/**
 * CoverArt / flyer attachment meta validation (docs/coverart_flyer_spec.md).
 *
 * @package S2J_Webinar
 */

declare(strict_types=1);

namespace S2J\Webinar\Meta;

use S2J\Webinar\Constants;
use WP_Post;

final class OperationalMaterialMeta
{
    private const COVER_ART_MAX_BYTES = 5 * 1024 * 1024;

    private const FLYER_MAX_BYTES = 20 * 1024 * 1024;

    /** @var list<string> */
    private const COVER_ART_MIMES = array(
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
    );

    public static function register(): void
    {
        add_filter('update_post_metadata', array( self::class, 'filter_update_post_metadata' ), 10, 5);
        add_action('save_post', array( self::class, 'on_save_post' ), 15, 2);
    }

    /**
     * @return list<string>
     */
    public static function meta_keys(): array
    {
        return array(
            '_s2j_webinar_cover_art_id',
            '_s2j_webinar_flyer_id',
        );
    }

    /**
     * @param null|bool $check       Short-circuit value from prior filters.
     * @param int         $object_id   Post ID (update_post_metadata).
     * @param string      $meta_key    Meta key being updated.
     * @param mixed       $meta_value  Proposed meta value.
     * @param mixed       $prev_value  Previous meta value (unused).
     * @return null|bool Return non-null to short-circuit update_metadata().
     */
    public static function filter_update_post_metadata($check, int $object_id, string $meta_key, $meta_value, $prev_value)
    {
        unset($prev_value);
        if ($check !== null) {
            return $check;
        }
        if (! in_array($meta_key, self::meta_keys(), true)) {
            return null;
        }
        if ($object_id <= 0 || ! Constants::is_gatherpress_event($object_id)) {
            return null;
        }

        $id = is_numeric($meta_value) ? (int) $meta_value : 0;
        if ($id <= 0) {
            delete_post_meta($object_id, $meta_key);
            return true;
        }

        if (! self::attachment_allowed($meta_key, $id)) {
            return false;
        }

        return null;
    }

    public static function on_save_post(int $post_id, WP_Post $post): void
    {
        if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
            return;
        }
        if (! Constants::is_gatherpress_event($post_id)) {
            return;
        }

        foreach (self::meta_keys() as $key) {
            $raw = get_post_meta($post_id, $key, true);
            if ($raw === '' || $raw === false) {
                continue;
            }
            $id = (int) $raw;
            if ($id <= 0) {
                delete_post_meta($post_id, $key);
                continue;
            }
            if (! self::attachment_allowed($key, $id)) {
                delete_post_meta($post_id, $key);
            }
        }
    }

    /**
     * @param mixed $value
     */
    public static function sanitize_attachment_id($value): int
    {
        if ($value === '' || $value === null || $value === false) {
            return 0;
        }
        return max(0, (int) $value);
    }

    public static function attachment_allowed(string $meta_key, int $attachment_id): bool
    {
        $post = get_post($attachment_id);
        if (! $post instanceof WP_Post || $post->post_type !== 'attachment') {
            return false;
        }

        $mime = get_post_mime_type($attachment_id);
        if (! is_string($mime) || $mime === '') {
            return false;
        }

        if ($meta_key === '_s2j_webinar_cover_art_id') {
            if (! in_array($mime, self::COVER_ART_MIMES, true)) {
                return false;
            }
            return self::file_within_limit($attachment_id, self::COVER_ART_MAX_BYTES);
        }

        if ($meta_key === '_s2j_webinar_flyer_id') {
            if ($mime !== 'application/pdf') {
                return false;
            }
            return self::file_within_limit($attachment_id, self::FLYER_MAX_BYTES);
        }

        return false;
    }

    private static function file_within_limit(int $attachment_id, int $max_bytes): bool
    {
        $path = get_attached_file($attachment_id);
        if (! is_string($path) || $path === '' || ! is_readable($path)) {
            return false;
        }
        $size = filesize($path);
        if ($size === false) {
            return false;
        }

        return $size <= $max_bytes;
    }
}
