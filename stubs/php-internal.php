<?php
/**
 * IDE-only fallbacks when built-in stubs are not indexed (never load at runtime).
 *
 * @package S2J_Webinar
 */

declare(strict_types=1);

namespace {
	if ( ! function_exists( 'hash_hmac' ) ) {
		/**
		 * @param non-empty-string $algo
		 */
		function hash_hmac( string $algo, string $data, string $key, bool $binary = false ): string {
			unset( $algo, $data, $key, $binary );
			return '';
		}
	}

	if ( ! function_exists( 'hash_equals' ) ) {
		function hash_equals( string $known_string, string $user_string ): bool {
			unset( $known_string, $user_string );
			return false;
		}
	}

	if ( ! function_exists( 'preg_replace' ) ) {
		/**
		 * @param string|string[] $pattern
		 * @param string|string[] $replacement
		 * @param string|string[] $subject
		 * @return string|string[]|null
		 */
		function preg_replace( $pattern, $replacement, $subject, int $limit = -1, ?int &$count = null ) {
			unset( $pattern, $replacement, $subject, $limit, $count );
			return '';
		}
	}

	if ( ! function_exists( 'time' ) ) {
		function time(): int {
			return 0;
		}
	}

	if ( ! class_exists( 'DateTimeZone', false ) ) {
		class DateTimeZone {
			public function __construct( string $timezone ) {
				unset( $timezone );
			}
		}
	}

	if ( ! class_exists( 'DateTimeImmutable', false ) ) {
		class DateTimeImmutable {
			public function __construct( string $datetime, ?DateTimeZone $timezone = null ) {
				unset( $datetime, $timezone );
			}

			public static function createFromFormat( string $format, string $datetime, ?DateTimeZone $timezone = null ): DateTimeImmutable|false {
				unset( $format, $datetime, $timezone );
				return false;
			}

			public function format( string $format ): string {
				unset( $format );
				return '';
			}

			public function getTimestamp(): int {
				return 0;
			}
		}
	}
}
