<?php
/**
 * PHPStan / IDE stubs — GatherPress is not bundled with this plugin.
 *
 * @package S2J_Webinar
 */

declare(strict_types=1);

namespace GatherPress\Core;

class Settings
{
	public static function get_instance(): self
	{
		return new self();
	}

	public function render_settings_form( string $page ): void
	{
		unset( $page );
	}
}

class Utility
{
	public static function prefix_key( string $key ): string
	{
		return $key;
	}

	public static function unprefix_key( string $key ): string
	{
		return $key;
	}
}

namespace GatherPress\Core\Event;

class Event
{
	public function __construct( int $post_id )
	{
		unset( $post_id );
	}

	/** @return array<string, mixed> */
	public function get_datetime(): array
	{
		return array();
	}
}
