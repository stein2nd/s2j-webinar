<?php
/**
 * Recompute record status after panel meta is saved with the post.
 *
 * @package S2J_Webinar
 */

declare(strict_types=1);

namespace S2J\Webinar\Meta;

use S2J\Webinar\Constants;
use S2J\Webinar\Record\RecordAssembler;
use WP_Post;

final class PanelMetaSaver {

	public static function register(): void {
		add_action( 'save_post', array( self::class, 'on_save_post' ), 20, 2 );
	}

	public static function on_save_post( int $post_id, WP_Post $post ): void {
		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}
		if ( ! Constants::is_gatherpress_event( $post_id ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$record = RecordAssembler::from_post( $post );
		update_post_meta( $post_id, '_s2j_webinar_status', $record['status'] );
	}
}
