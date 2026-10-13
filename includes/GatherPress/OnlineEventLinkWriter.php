<?php
/**
 * Write gatherpress_online_event_link per gatherpress_boundary_spec.md.
 *
 * @package S2J_Webinar
 */

declare(strict_types=1);

namespace S2J\Webinar\GatherPress;

final class OnlineEventLinkWriter {

	public static function set_join_url( int $post_id, string $join_url ): void {
		$join_url = esc_url_raw( $join_url );
		if ( $join_url === '' ) {
			return;
		}

		if ( class_exists( '\GatherPress\Core\Event' ) && method_exists( '\GatherPress\Core\Event', 'get_instance' ) ) {
			$event = \GatherPress\Core\Event::get_instance( $post_id );
			if ( is_object( $event ) && method_exists( $event, 'set_online' ) ) {
				$event->set_online( $join_url );
				return;
			}
		}

		update_post_meta( $post_id, 'gatherpress_online_event_link', $join_url );
	}

	public static function clear( int $post_id ): void {
		delete_post_meta( $post_id, 'gatherpress_online_event_link' );
	}
}
