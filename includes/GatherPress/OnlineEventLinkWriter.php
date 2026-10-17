<?php

/**
 * Write gatherpress_online_event_link per gatherpress_boundary_spec.md.
 *
 * @package S2J_Webinar
 */

declare(strict_types=1);

namespace S2J\Webinar\GatherPress;

final class OnlineEventLinkWriter
{
    public static function set_join_url(int $post_id, string $join_url): void
    {
        $join_url = esc_url_raw($join_url);
        if ($join_url === '') {
            return;
        }

        if (class_exists('\GatherPress\Core\Event\Event')) {
            $event = new \GatherPress\Core\Event\Event($post_id);
            if (self::apply_gatherpress_online_link($event, $join_url)) {
                return;
            }
        }

        update_post_meta($post_id, 'gatherpress_online_event_link', $join_url);
    }

    /**
     * @param object $event GatherPress event instance (may expose set_online on some GP versions).
     */
    private static function apply_gatherpress_online_link(object $event, string $join_url): bool
    {
        if (! is_callable(array( $event, 'set_online' ))) {
            return false;
        }
        call_user_func(array( $event, 'set_online' ), $join_url);
        return true;
    }

    public static function clear(int $post_id): void
    {
        delete_post_meta($post_id, 'gatherpress_online_event_link');
    }
}
