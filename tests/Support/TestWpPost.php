<?php

declare(strict_types=1);

namespace S2J\Webinar\Tests\Support;

final class TestWpPost {

	public static function make( int $id, string $title = '', string $excerpt = '' ): \WP_Post {
		return new \WP_Post(
			(object) array(
				'ID'           => $id,
				'post_title'   => $title,
				'post_excerpt' => $excerpt,
			)
		);
	}
}
