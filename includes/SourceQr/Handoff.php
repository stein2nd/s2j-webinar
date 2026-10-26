<?php

/**
 * Source QR panel handoff (editor hooks; UI in Gutenberg).
 *
 * @package S2J_Webinar
 */

declare(strict_types=1);

namespace S2J\Webinar\SourceQr;

use S2J\Webinar\Companion\CompanionStatus;

final class Handoff
{
    /** @wordpress/hooks action name — Source QR registers the listener. */
    public const OPEN_HOOK = 's2j_webinar.open_source_qr';

    public static function is_companion_active(): bool
    {
        return CompanionStatus::is_source_qr_active();
    }
}
