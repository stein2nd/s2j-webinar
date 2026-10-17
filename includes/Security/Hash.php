<?php

/**
 * Thin wrappers for PHP hash helpers (namespaced call sites + IDE-friendly).
 *
 * @package S2J_Webinar
 */

declare(strict_types=1);

namespace S2J\Webinar\Security;

final class Hash
{
    public static function hmac_sha256(string $data, string $secret): string
    {
        return (string) \call_user_func('hash_hmac', 'sha256', $data, $secret);
    }

    public static function equals(string $known_string, string $user_string): bool
    {
        return \hash_equals($known_string, $user_string);
    }
}
