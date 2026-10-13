<?php
/**
 * Zoom OAuth connect / callback / disconnect (admin-post).
 *
 * @package S2J_Webinar
 */

declare(strict_types=1);

namespace S2J\Webinar\OAuth;

use S2J\Webinar\Constants;
use S2J\Webinar\Http\ZoomHttpClient;

use function S2J\WebinarService\build_oauth_authorize_url;

final class OAuthFlow {

	public static function register(): void {
		add_filter( 'allowed_redirect_hosts', array( self::class, 'allow_oauth_redirect_hosts' ) );
		add_action( 'admin_post_' . Constants::OAUTH_CALLBACK_ACTION, array( self::class, 'handle_callback' ) );
		add_action( 'admin_post_s2j_webinar_oauth_connect', array( self::class, 'handle_connect' ) );
		add_action( 'admin_post_s2j_webinar_oauth_disconnect', array( self::class, 'handle_disconnect' ) );
	}

	/**
	 * @param string[] $hosts
	 * @return string[]
	 */
	public static function allow_oauth_redirect_hosts( array $hosts ): array {
		$hosts[] = 'zoom.us';
		return $hosts;
	}

	public static function handle_connect(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Forbidden', 's2j-webinar' ) );
		}
		check_admin_referer( 's2j_webinar_oauth_connect' );

		$config = TokenStore::oauth_config();
		if ( $config['client_id'] === '' || $config['client_secret'] === '' ) {
			self::redirect_settings( 'missing_credentials' );
		}

		$url = build_oauth_authorize_url( $config );
		wp_safe_redirect( $url );
		exit;
	}

	public static function handle_disconnect(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Forbidden', 's2j-webinar' ) );
		}
		check_admin_referer( 's2j_webinar_oauth_disconnect' );

		TokenStore::disconnect();
		self::redirect_settings( 'disconnected' );
	}

	public static function handle_callback(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Forbidden', 's2j-webinar' ) );
		}

		$code = isset( $_GET['code'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['code'] ) ) : '';
		if ( $code === '' ) {
			self::redirect_settings( 'oauth_denied' );
		}

		$result = ZoomHttpClient::exchange_authorization_code( $code );
		if ( $result['status'] < 200 || $result['status'] >= 300 || ! is_string( $result['body'] ) ) {
			self::redirect_settings( 'oauth_exchange_failed' );
		}

		$data = json_decode( $result['body'], true );
		if ( ! is_array( $data ) ) {
			self::redirect_settings( 'oauth_exchange_failed' );
		}

		TokenStore::persist_token_response( $data );
		self::redirect_settings( 'connected' );
	}

	private static function redirect_settings( string $notice ): void {
		$url = add_query_arg(
			array(
				'page'                => 's2j-webinar',
				's2j_webinar_notice'  => $notice,
			),
			admin_url( 'admin.php' )
		);
		wp_safe_redirect( $url );
		exit;
	}
}
