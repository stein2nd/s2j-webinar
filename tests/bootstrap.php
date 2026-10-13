<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

use S2J\Webinar\Tests\Support\WpFunctionStub;

if ( ! function_exists( '__' ) ) {
	/**
	 * @param string $text
	 */
	function __( $text, $domain = 'default' ) {
		unset( $domain );
		return $text;
	}
}

if ( ! function_exists( 'esc_url_raw' ) ) {
	/**
	 * @param string $url
	 */
	function esc_url_raw( $url ) {
		return $url;
	}
}

/** @var array<string, list<callable>> */
$GLOBALS['s2j_test_filters'] = array();

if ( ! function_exists( 'add_filter' ) ) {
	/**
	 * @param callable $callback
	 */
	function add_filter( string $tag, $callback, int $priority = 10, int $accepted_args = 1 ): void {
		unset( $priority, $accepted_args );
		$GLOBALS['s2j_test_filters'][ $tag ][] = $callback;
	}
}

if ( ! function_exists( 'apply_filters' ) ) {
	/**
	 * @param mixed $value
	 * @param mixed ...$args
	 * @return mixed
	 */
	function apply_filters( string $tag, $value, ...$args ) {
		if ( ! isset( $GLOBALS['s2j_test_filters'][ $tag ] ) ) {
			return $value;
		}
		foreach ( $GLOBALS['s2j_test_filters'][ $tag ] as $callback ) {
			$value = $callback( $value, ...$args );
		}
		return $value;
	}
}

WpFunctionStub::register();
/** @var array<int, array<string, mixed>> $s2j_unit_test_post_meta */
$s2j_unit_test_post_meta = require __DIR__ . '/Support/unit-test-default-post-meta.php';
WpFunctionStub::seed( array(), $s2j_unit_test_post_meta );

if ( ! function_exists( 'wp_json_encode' ) ) {
	/**
	 * @param mixed $data
	 */
	function wp_json_encode( $data, int $options = 0, int $depth = 512 ): string|false {
		return json_encode( $data, $options, $depth );
	}
}

if ( ! class_exists( 'WP_Post', false ) ) {
	class WP_Post {
		public int $ID = 0;

		public string $post_title = '';

		public string $post_excerpt = '';

		/**
		 * @param object|null $post Row-like object (matches WordPress stub signature).
		 */
		public function __construct( ?object $post = null ) {
			if ( ! is_object( $post ) ) {
				return;
			}
			if ( isset( $post->ID ) ) {
				$this->ID = (int) $post->ID;
			}
			if ( isset( $post->post_title ) ) {
				$this->post_title = (string) $post->post_title;
			}
			if ( isset( $post->post_excerpt ) ) {
				$this->post_excerpt = (string) $post->post_excerpt;
			}
		}
	}
}
