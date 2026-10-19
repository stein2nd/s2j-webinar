<?php

declare(strict_types=1);

namespace S2J\Webinar\Tests\Integration;

if ( ! class_exists( \WP_UnitTestCase::class, false ) ) {
	require_once dirname( __DIR__, 2 ) . '/stubs/wordpress-tests.php';
}

use WP_UnitTestCase;

final class RestRoutesTest extends WP_UnitTestCase {

	public function test_sync_route_is_registered(): void {
		$server = rest_get_server();
		$routes = array_keys( $server->get_routes() );
		$this->assertNotEmpty( $this->matching_routes( $routes, 's2j-webinar/v1/events', '/sync' ) );
		$this->assertNotEmpty( $this->matching_routes( $routes, 's2j-webinar/v1/events', '/open' ) );
		$this->assertNotEmpty( $this->matching_routes( $routes, 's2j-webinar/v1/events', '/delete' ) );
		$this->assertNotEmpty(
			$this->matching_routes( $routes, 's2j-webinar/v1/events', '/tracking-source-definitions' )
		);
		$this->assertNotEmpty(
			$this->matching_routes( $routes, 's2j-webinar/v1/events', '/tracking-sources' )
		);
		$this->assertNotEmpty( $this->matching_routes( $routes, 's2j-webinar/v1/webhook/zoom', '' ) );
	}

	/**
	 * @param list<string> $routes
	 * @return list<string>
	 */
	private function matching_routes( array $routes, string $namespace_fragment, string $suffix ): array {
		$out = array();
		foreach ( $routes as $route ) {
			if ( ! str_contains( $route, $namespace_fragment ) ) {
				continue;
			}
			if ( $suffix !== '' && ! str_contains( $route, $suffix ) ) {
				continue;
			}
			$out[] = $route;
		}
		return $out;
	}
}
