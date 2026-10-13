<?php

declare(strict_types=1);

if ( ! class_exists( 'WP_REST_Request', false ) ) {
	class WP_REST_Request {
		/** @var array<string, string> */
		private array $headers = array();

		private string $body = '';

		public function set_header( string $name, string $value ): void {
			$this->headers[ strtolower( $name ) ] = $value;
		}

		public function get_header( string $name ): ?string {
			return $this->headers[ strtolower( $name ) ] ?? null;
		}

		public function set_body( string $body ): void {
			$this->body = $body;
		}

		public function get_body(): string {
			return $this->body;
		}

		/**
		 * @return array<string, mixed>|null
		 */
		public function get_json_params(): ?array {
			$decoded = json_decode( $this->body, true );
			return is_array( $decoded ) ? $decoded : null;
		}
	}
}

if ( ! class_exists( 'WP_REST_Response', false ) ) {
	class WP_REST_Response {
		/** @var mixed */
		private $data;

		private int $status;

		/**
		 * @param mixed $data
		 */
		public function __construct( $data, int $status ) {
			$this->data   = $data;
			$this->status = $status;
		}

		public function get_status(): int {
			return $this->status;
		}
	}
}

if ( ! class_exists( 'WP_Query', false ) ) {
	class WP_Query {
		/** @var list<int> */
		public array $posts = array();

		/**
		 * @param array<string, mixed> $query
		 */
		public function __construct( array $query ) {
			unset( $query );
		}

		public function have_posts(): bool {
			return $this->posts !== array();
		}
	}
}
