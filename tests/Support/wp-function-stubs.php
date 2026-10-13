<?php

declare(strict_types=1);

use S2J\Webinar\Tests\Support\WpFunctionStub;

if ( ! function_exists( 'get_option' ) ) {
	/**
	 * @param mixed $default
	 * @return mixed
	 */
	function get_option( string $option, $default = false ) {
		return WpFunctionStub::$options[ $option ] ?? $default;
	}
}

if ( ! function_exists( 'update_option' ) ) {
	/**
	 * @param mixed $value
	 */
	function update_option( string $option, $value, $autoload = null ): bool {
		unset( $autoload );
		WpFunctionStub::$options[ $option ] = $value;
		return true;
	}
}

if ( ! function_exists( 'get_post_meta' ) ) {
	/**
	 * @return mixed
	 */
	function get_post_meta( int $post_id, string $key = '', bool $single = false ) {
		unset( $single );
		if ( ! isset( WpFunctionStub::$post_meta[ $post_id ][ $key ] ) ) {
			return '';
		}
		return WpFunctionStub::$post_meta[ $post_id ][ $key ];
	}
}

if ( ! function_exists( 'update_post_meta' ) ) {
	/**
	 * @param mixed $value
	 */
	function update_post_meta( int $post_id, string $key, $value ): int|bool {
		WpFunctionStub::$post_meta[ $post_id ][ $key ] = $value;
		return true;
	}
}

if ( ! function_exists( 'delete_post_meta' ) ) {
	function delete_post_meta( int $post_id, string $key, $value = '' ): bool {
		unset( $value );
		unset( WpFunctionStub::$post_meta[ $post_id ][ $key ] );
		return true;
	}
}

if ( ! function_exists( 'wp_remote_request' ) ) {
	/**
	 * @param array<string, mixed> $args
	 * @return array<string, mixed>
	 */
	function wp_remote_request( string $url, array $args = array() ) {
		$handler = WpFunctionStub::$remote_request_handler;
		if ( is_callable( $handler ) ) {
			return $handler( $url, $args );
		}
		return array(
			'response' => array( 'code' => 500 ),
			'body'     => '',
		);
	}
}

if ( ! function_exists( 'wp_remote_retrieve_response_code' ) ) {
	/**
	 * @param array<string, mixed> $response
	 */
	function wp_remote_retrieve_response_code( $response ): int {
		if ( is_array( $response ) && isset( $response['response']['code'] ) ) {
			return (int) $response['response']['code'];
		}
		return 0;
	}
}

if ( ! function_exists( 'wp_remote_retrieve_body' ) ) {
	/**
	 * @param array<string, mixed> $response
	 */
	function wp_remote_retrieve_body( $response ): string {
		if ( is_array( $response ) && isset( $response['body'] ) && is_string( $response['body'] ) ) {
			return $response['body'];
		}
		return '';
	}
}

if ( ! function_exists( 'is_wp_error' ) ) {
	/**
	 * @param mixed $thing
	 */
	function is_wp_error( $thing ): bool {
		unset( $thing );
		return false;
	}
}

if ( ! function_exists( 'admin_url' ) ) {
	function admin_url( string $path = '' ): string {
		return 'https://example.test/wp-admin/' . ltrim( $path, '/' );
	}
}

if ( ! function_exists( 'esc_url_raw' ) ) {
	/**
	 * @param string $url
	 */
	function esc_url_raw( $url ): string {
		return $url;
	}
}

if ( ! function_exists( 'wp_timezone_string' ) ) {
	function wp_timezone_string(): string {
		return 'Asia/Tokyo';
	}
}

if ( ! function_exists( 'post_type_supports' ) ) {
	function post_type_supports( string $post_type, string $feature ): bool {
		unset( $post_type, $feature );
		return true;
	}
}

if ( ! function_exists( 'get_post_type' ) ) {
	function get_post_type( int $post_id ): string {
		unset( $post_id );
		return 'gatherpress_event';
	}
}
