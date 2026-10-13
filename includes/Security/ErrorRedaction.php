<?php
/**
 * Strip token-like substrings from user-visible errors.
 *
 * @package S2J_Webinar
 */

declare(strict_types=1);

namespace S2J\Webinar\Security;

final class ErrorRedaction {

	public static function scrub( string $message ): string {
		$message = Regex::replace( '/Bearer\s+[A-Za-z0-9._\-]+/i', 'Bearer [redacted]', $message );
		$message = Regex::replace( '/access_token[=:]\s*[A-Za-z0-9._\-]+/i', 'access_token=[redacted]', $message );
		$message = Regex::replace( '/refresh_token[=:]\s*[A-Za-z0-9._\-]+/i', 'refresh_token=[redacted]', $message );
		return $message;
	}
}
