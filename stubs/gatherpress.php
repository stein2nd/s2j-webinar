<?php
/**
 * IDE stubs only — GatherPress is not bundled with this plugin.
 *
 * @package S2J_Webinar
 */

declare(strict_types=1);

namespace GatherPress\Core {

	class Settings {
		public static function get_instance(): self {
			return new self();
		}

		public function render_settings_form( string $page ): void {
		}
	}

	class Utility {
		public static function prefix_key( string $key ): string {
			return $key;
		}

		public static function unprefix_key( string $key ): string {
			return $key;
		}
	}

	class Event {
		public static function get_instance( int $post_id ): self {
			unset( $post_id );
			return new self();
		}

		/** @return array<string, mixed> */
		public function get_datetime(): array {
			return array();
		}

		public function set_online( string $join_url ): void {
			unset( $join_url );
		}
	}
}
