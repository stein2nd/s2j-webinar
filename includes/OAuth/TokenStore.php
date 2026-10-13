<?php
/**
 * Site options for Zoom OAuth tokens and secrets.
 *
 * @package S2J_Webinar
 */

declare(strict_types=1);

namespace S2J\Webinar\OAuth;

use function \time;

final class TokenStore {

	public static function get_client_id(): string {
		return (string) get_option( 's2j_webinar_zoom_client_id', '' );
	}

	public static function get_client_secret(): string {
		return (string) get_option( 's2j_webinar_zoom_client_secret', '' );
	}

	public static function get_access_token(): string {
		return (string) get_option( 's2j_webinar_zoom_access_token', '' );
	}

	public static function get_refresh_token(): string {
		return (string) get_option( 's2j_webinar_zoom_refresh_token', '' );
	}

	public static function get_expires_at(): int {
		return (int) get_option( 's2j_webinar_zoom_token_expires_at', 0 );
	}

	public static function is_connected(): bool {
		return self::get_access_token() !== '';
	}

	/**
	 * @return array<string, string>
	 */
	public static function oauth_config(): array {
		return array(
			'client_id'     => self::get_client_id(),
			'client_secret' => self::get_client_secret(),
			'redirect_uri'  => self::redirect_uri(),
		);
	}

	public static function redirect_uri(): string {
		return admin_url( 'admin-post.php?action=' . \S2J\Webinar\Constants::OAUTH_CALLBACK_ACTION );
	}

	public static function disconnect(): void {
		delete_option( 's2j_webinar_zoom_access_token' );
		delete_option( 's2j_webinar_zoom_refresh_token' );
		delete_option( 's2j_webinar_zoom_token_expires_at' );
		delete_option( 's2j_webinar_zoom_account_email' );
	}

	/**
	 * @param array<string, mixed> $data
	 */
	public static function persist_token_response( array $data ): void {
		if ( isset( $data['access_token'] ) && is_string( $data['access_token'] ) ) {
			update_option( 's2j_webinar_zoom_access_token', $data['access_token'], false );
		}
		if ( isset( $data['refresh_token'] ) && is_string( $data['refresh_token'] ) ) {
			update_option( 's2j_webinar_zoom_refresh_token', $data['refresh_token'], false );
		}
		if ( isset( $data['expires_in'] ) && is_numeric( $data['expires_in'] ) ) {
			update_option( 's2j_webinar_zoom_token_expires_at', time() + (int) $data['expires_in'], false );
		}
		if ( isset( $data['email'] ) && is_string( $data['email'] ) && $data['email'] !== '' ) {
			update_option( 's2j_webinar_zoom_account_email', sanitize_email( $data['email'] ), false );
		}
	}

	public static function get_account_email(): string {
		return (string) get_option( 's2j_webinar_zoom_account_email', '' );
	}
}
