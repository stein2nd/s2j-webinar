<?php
/**
 * register_post_meta for panel and server keys.
 *
 * @package S2J_Webinar
 */

declare(strict_types=1);

namespace S2J\Webinar\Meta;

use S2J\Webinar\Constants;

final class PostMetaRegistrar {

	public static function register(): void {
		add_action( 'init', array( self::class, 'register_meta' ), 20 );
	}

	public static function register_meta(): void {
		$post_types = self::event_post_types();
		if ( $post_types === array() ) {
			return;
		}

		foreach ( $post_types as $post_type ) {
			self::register_panel_keys( $post_type );
			self::register_server_keys( $post_type );
		}
	}

	/**
	 * @return list<string>
	 */
	private static function event_post_types(): array {
		$types = get_post_types( array( 'public' => true ), 'names' );
		$out   = array();
		foreach ( $types as $type ) {
			if ( post_type_supports( $type, 'gatherpress-event-date' ) ) {
				$out[] = $type;
			}
		}
		return $out;
	}

	private static function register_panel_keys( string $post_type ): void {
		register_post_meta(
			$post_type,
			'_s2j_webinar_provider',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'auth_callback'     => array( self::class, 'can_edit_post_meta' ),
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => Constants::DEFAULT_PROVIDER,
			)
		);

		register_post_meta(
			$post_type,
			'_s2j_webinar_auto_recording',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'auth_callback'     => array( self::class, 'can_edit_post_meta' ),
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => 'cloud',
			)
		);

		register_post_meta(
			$post_type,
			'_s2j_webinar_approval_type',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'auth_callback'     => array( self::class, 'can_edit_post_meta' ),
				'sanitize_callback' => array( self::class, 'sanitize_approval_type' ),
				'default'           => '0',
			)
		);

		register_post_meta(
			$post_type,
			'_s2j_webinar_panelists',
			array(
				'type'              => 'array',
				'single'            => true,
				'show_in_rest'      => array(
					'schema' => array(
						'type'  => 'array',
						'items' => array(
							'type'       => 'object',
							'properties' => array(
								'name'  => array( 'type' => 'string' ),
								'email' => array( 'type' => 'string' ),
							),
						),
					),
				),
				'auth_callback'     => array( self::class, 'can_edit_post_meta' ),
				'sanitize_callback' => array( self::class, 'sanitize_panelists' ),
				'default'           => array(),
			)
		);
	}

	private static function register_server_keys( string $post_type ): void {
		$scalar_string = array(
			'type'          => 'string',
			'single'        => true,
			'show_in_rest'  => false,
			'auth_callback' => array( self::class, 'can_edit_post_meta' ),
		);

		foreach ( array( '_s2j_webinar_id', '_s2j_webinar_uuid', '_s2j_webinar_join_url' ) as $key ) {
			register_post_meta( $post_type, $key, $scalar_string );
		}

		foreach ( array( '_s2j_webinar_status', '_s2j_webinar_last_error' ) as $key ) {
			register_post_meta(
				$post_type,
				$key,
				array(
					'type'              => 'string',
					'single'            => true,
					'show_in_rest'      => true,
					'auth_callback'     => '__return_false',
					'sanitize_callback' => 'sanitize_text_field',
				)
			);
		}

		register_post_meta(
			$post_type,
			'_s2j_webinar_last_sent',
			array(
				'type'              => 'object',
				'single'            => true,
				'show_in_rest'      => false,
				'auth_callback'     => array( self::class, 'can_edit_post_meta' ),
				'sanitize_callback' => array( self::class, 'sanitize_json_object' ),
			)
		);

		register_post_meta(
			$post_type,
			'_s2j_webinar_last_panelists',
			array(
				'type'              => 'array',
				'single'            => true,
				'show_in_rest'      => false,
				'auth_callback'     => array( self::class, 'can_edit_post_meta' ),
				'sanitize_callback' => array( self::class, 'sanitize_panelists' ),
			)
		);

		register_post_meta(
			$post_type,
			'_s2j_webinar_session',
			array(
				'type'              => 'object',
				'single'            => true,
				'show_in_rest'      => false,
				'auth_callback'     => array( self::class, 'can_edit_post_meta' ),
				'sanitize_callback' => array( self::class, 'sanitize_json_object' ),
			)
		);
	}

	public static function can_edit_post_meta( bool $allowed, string $meta_key, int $post_id ): bool {
		unset( $allowed, $meta_key );
		return current_user_can( 'edit_post', $post_id );
	}

	/**
	 * @param mixed $value
	 */
	public static function sanitize_approval_type( $value ): string {
		$value = is_scalar( $value ) ? (string) $value : '0';
		return in_array( $value, array( '0', '1', '2' ), true ) ? $value : '0';
	}

	/**
	 * @param mixed $value
	 * @return list<array{name: string, email: string}>
	 */
	public static function sanitize_panelists( $value ): array {
		if ( ! is_array( $value ) ) {
			return array();
		}
		$out = array();
		foreach ( $value as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$name  = isset( $row['name'] ) ? sanitize_text_field( (string) $row['name'] ) : '';
			$email = isset( $row['email'] ) ? sanitize_email( (string) $row['email'] ) : '';
			if ( $email === '' ) {
				continue;
			}
			$out[] = array(
				'name'  => $name,
				'email' => $email,
			);
		}
		return $out;
	}

	/**
	 * @param mixed $value
	 * @return array<string, mixed>
	 */
	public static function sanitize_json_object( $value ): array {
		return is_array( $value ) ? $value : array();
	}
}
