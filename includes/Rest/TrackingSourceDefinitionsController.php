<?php

/**
 * GET tracking-source-definitions for S2J Webinar Source QR.
 *
 * @package S2J_Webinar
 */

declare(strict_types=1);

namespace S2J\Webinar\Rest;

use S2J\Webinar\Constants;
use S2J\Webinar\SourceQr\TrackingSourceDefinitions;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

final class TrackingSourceDefinitionsController
{
    public static function register(): void
    {
        add_action('rest_api_init', array( self::class, 'register_routes' ));
    }

    public static function register_routes(): void
    {
        register_rest_route(
            Constants::REST_NAMESPACE,
            '/events/(?P<post_id>\d+)/tracking-source-definitions',
            array(
                'methods'             => 'GET',
                'callback'            => array( self::class, 'handle_get' ),
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

    public static function handle_get(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $post_id = (int) $request['post_id'];
        if (! get_post($post_id)) {
            return new WP_Error('rest_post_invalid_id', __('Invalid post ID.', 's2j-webinar'), array( 'status' => 404 ));
        }

        $webinar_id = trim((string) get_post_meta($post_id, '_s2j_webinar_id', true));
        if ($webinar_id === '') {
            return new WP_Error(
                'tracking_webinar_id_empty',
                __('Connect this event to a webinar before loading source definitions.', 's2j-webinar'),
                array( 'status' => 400 )
            );
        }

        $provider = trim((string) get_post_meta($post_id, '_s2j_webinar_provider', true));
        if ($provider === '') {
            $provider = Constants::DEFAULT_PROVIDER;
        }

        $sources = TrackingSourceDefinitions::for_event($post_id, $provider);

        return new WP_REST_Response(
            array(
                'sources' => $sources,
            ),
            200
        );
    }
}
