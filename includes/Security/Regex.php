<?php

/**
 * Thin wrapper for preg_replace (namespaced call sites + IDE-friendly).
 *
 * @package S2J_Webinar
 */

declare(strict_types=1);

namespace S2J\Webinar\Security;

final class Regex
{
    public static function replace(string $pattern, string $replacement, string $subject): string
    {
        return \preg_replace($pattern, $replacement, $subject) ?? $subject;
    }
}
