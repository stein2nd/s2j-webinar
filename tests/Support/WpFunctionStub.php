<?php

declare(strict_types=1);

namespace S2J\Webinar\Tests\Support;

/**
 * In-memory WordPress function stubs for pure PHPUnit (SyncRunner, OAuth, etc.).
 */
final class WpFunctionStub {

	/** @var array<string, mixed> */
	public static array $options = array();

	/** @var array<int, array<string, mixed>> */
	public static array $post_meta = array();

	/** @var callable|null */
	public static $remote_request_handler = null;

	public static function reset(): void {
		self::$options                = array();
		self::$post_meta              = array();
		self::$remote_request_handler = null;
	}

	/**
	 * @param array<string, mixed> $options
	 * @param array<int, array<string, mixed>> $post_meta
	 */
	public static function seed( array $options = array(), array $post_meta = array() ): void {
		self::$options   = $options;
		self::$post_meta = $post_meta;
	}

	public static function seedUnitTestDefaults(): void {
		/** @var array<int, array<string, mixed>> $post_meta */
		$post_meta = require __DIR__ . '/unit-test-default-post-meta.php';
		self::seed( array(), $post_meta );
	}

	public static function register(): void {
		static $loaded = false;
		if ( $loaded ) {
			return;
		}
		$loaded = true;
		require_once __DIR__ . '/wp-function-stubs.php';
	}
}
