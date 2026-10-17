<?php

/**
 * REST: sync (see SyncController), open in Zoom, delete from Zoom.
 *
 * @package S2J_Webinar
 */

declare(strict_types=1);

namespace S2J\Webinar\Rest;

use S2J\Webinar\Constants;
use S2J\Webinar\Sync\DeleteRunner;
use S2J\Webinar\Sync\GetRunner;
use WP_Error;
use WP_Post;
use WP_REST_Request;
use WP_REST_Response;

final class EventActionsController
{
    public static function register(): void
    {
        add_action('rest_api_init', array( self::class, 'register_routes' ));
    }

    public static function register_routes(): void
    {
        register_rest_route(
            Constants::REST_NAMESPACE,
            '/events/(?P<post_id>\d+)/open',
            array(
                'methods'             => 'POST',
                'callback'            => array( self::class, 'handle_open' ),
                'permission_callback' => array( SyncController::class, 'can_edit_event' ),
                'args'                => array(
                    'post_id' => array(
                        'type'              => 'integer',
                        'required'          => true,
                        'sanitize_callback' => 'absint',
                    ),
                ),
            )
        );

        register_rest_route(
            Constants::REST_NAMESPACE,
            '/events/(?P<post_id>\d+)/delete',
            array(
                'methods'             => 'POST',
                'callback'            => array( self::class, 'handle_delete' ),
                'permission_callback' => array( SyncController::class, 'can_edit_event' ),
                'args'                => array(
                    'post_id' => array(
                        'type'              => 'integer',
                        'required'          => true,
                        'sanitize_callback' => 'absint',
                    ),
                ),
            )
        );
    }

    public static function handle_open(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $post = self::get_event_post($request);
        if ($post instanceof WP_Error) {
            return $post;
        }

        $result = GetRunner::open_in_zoom($post);

        return new WP_REST_Response(
            array(
                'ok'        => $result['ok'],
                'message'   => $result['message'],
                'start_url' => $result['start_url'] ?? '',
            ),
            $result['ok'] ? 200 : 422
        );
    }

    public static function handle_delete(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $post = self::get_event_post($request);
        if ($post instanceof WP_Error) {
            return $post;
        }

        $result = DeleteRunner::delete_from_zoom($post);

        return new WP_REST_Response(
            array(
                'ok'            => $result['ok'],
                'message'       => $result['message'],
                'user_messages' => $result['user_messages'] ?? array(),
            ),
            $result['ok'] ? 200 : 422
        );
    }

    private static function get_event_post(WP_REST_Request $request): WP_Post|WP_Error
    {
        $post_id = (int) $request['post_id'];
        $post    = get_post($post_id);
        if (! $post instanceof WP_Post) {
            return new WP_Error('rest_post_invalid', __('Invalid post.', 's2j-webinar'), array( 'status' => 404 ));
        }
        return $post;
    }
}
