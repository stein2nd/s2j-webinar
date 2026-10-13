<?php
/**
 * Indirection for Zoom webinar HTTP (tests inject a mock).
 *
 * @package S2J_Webinar
 */

declare(strict_types=1);

namespace S2J\Webinar\Http;

final class WebinarHttpExecutor {

	/** @var callable|null */
	public static $executor = null;

	/**
	 * @param array{method: string, path: string, body: array<string, mixed>|null} $material
	 * @return array{status: int, body: string|null, error: string|null}
	 */
	public static function execute( array $material ): array {
		$callable = self::$executor;
		if ( is_callable( $callable ) ) {
			return $callable( $material );
		}
		return ZoomHttpClient::execute_webinar_material( $material );
	}
}
