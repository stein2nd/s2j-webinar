<?php
/**
 * Plugin Name: S2J Webinar Studio smoke seed
 * Description: After GatherPress and S2J Webinar are active, adds one draft event for manual smoke tests.
 *
 * Source for studio/smoke-test-blueprint.json (writeFile step). Keep both in sync.
 */

add_action(
	'admin_init',
	static function (): void {
		if ( ! is_admin() || ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		if ( get_option( 's2j_webinar_studio_smoke_seeded' ) ) {
			return;
		}

		if ( ! is_plugin_active( 'gatherpress/gatherpress.php' ) ) {
			return;
		}

		$webinar_plugin = 's2j-webinar/s2j-webinar.php';
		if ( ! is_plugin_active( $webinar_plugin ) ) {
			return;
		}

		$event_type = null;
		foreach ( get_post_types( array( 'public' => true ), 'names' ) as $type ) {
			if ( post_type_supports( $type, 'gatherpress-event-date' ) ) {
				$event_type = $type;
				break;
			}
		}

		if ( ! $event_type ) {
			return;
		}

		$post_id = wp_insert_post(
			array(
				'post_type'    => $event_type,
				'post_status'  => 'draft',
				'post_title'   => 'S2J Webinar smoke event',
				'post_excerpt' => 'Studio blueprint sample for sync smoke tests.',
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			return;
		}

		update_post_meta( $post_id, '_s2j_webinar_provider', 'zoom' );
		update_post_meta( $post_id, '_s2j_webinar_auto_recording', 'cloud' );
		update_post_meta( $post_id, '_s2j_webinar_approval_type', '0' );
		update_post_meta( $post_id, '_s2j_webinar_panelists', array() );
		update_option( 's2j_webinar_studio_smoke_seeded', 1, false );
	}
);
