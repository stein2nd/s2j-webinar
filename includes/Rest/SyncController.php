<?php

/**
 * REST endpoint to trigger sync from the event panel.
 *
 * @package S2J_Webinar
 */

declare(strict_types=1);

namespace S2J\Webinar\Rest;

use S2J\Webinar\Constants;
use S2J\Webinar\Sync\SyncRunner;
use WP_Error;
use WP_Post;
use WP_REST_Request;
use WP_REST_Response;

final class SyncController
{
    public static function register(): void
    {
        add_action('rest_api_init', array( self::class, 'register_routes' ));
    }

    public static function register_routes(): void
    {
        register_rest_route(
            Constants::REST_NAMESPACE,
            '/events/(?P<post_id>\d+)/sync',
            array(
                'methods'             => 'POST',
                'callback'            => array( self::class, 'handle_sync' ),
                'permission_callback' => array( self::class, 'can_edit_event' ),
                'args'                => array(
                    'post_id'      => array(
                        'type'              => 'integer',
                        'required'          => true,
                        'sanitize_callback' => 'absint',
                    ),
                    'intend_retry' => array(
                        'type'    => 'boolean',
                        'default' => false,
                    ),
                ),
            )
        );
    }

    public static function can_edit_event(WP_REST_Request $request): bool|WP_Error
    {
        $post_id = (int) $request['post_id'];
        if (! Constants::is_gatherpress_event($post_id)) {
            return new WP_Error('rest_invalid_post_type', __('Not a GatherPress event.', 's2j-webinar'), array( 'status' => 400 ));
        }
        if (! current_user_can('edit_post', $post_id)) {
            return new WP_Error('rest_forbidden', __('Forbidden.', 's2j-webinar'), array( 'status' => 403 ));
        }
        return true;
    }

    public static function handle_sync(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $post_id = (int) $request['post_id'];
        $post    = get_post($post_id);
        if (! $post instanceof WP_Post) {
            return new WP_Error('rest_post_invalid', __('Invalid post.', 's2j-webinar'), array( 'status' => 404 ));
        }

        $intend_retry = (bool) $request->get_param('intend_retry');
        $result       = SyncRunner::sync_post($post, $intend_retry);

        $body = array(
            'ok'      => $result['ok'],
            'message' => $result['message'],
        );
        if (isset($result['user_messages'])) {
            $body['user_messages'] = $result['user_messages'];
        }

        return new WP_REST_Response($body, $result['ok'] ? 200 : 422);
    }
}
