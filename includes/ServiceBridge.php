<?php

/**
 * Adapter to s2j/webinar-service pure functions.
 *
 * @package S2J_Webinar
 */

declare(strict_types=1);

namespace S2J\Webinar;

use function S2J\WebinarService\build_webinar_request;
use function S2J\WebinarService\normalize_webinar_record;
use function S2J\WebinarService\validate_webinar_record;

final class ServiceBridge
{
    public static function is_available(): bool
    {
        return function_exists('S2J\\WebinarService\\validate_webinar_record');
    }

    /**
     * @param array<string, mixed> $record
     * @return array<string, mixed>
     */
    public static function normalize_record(array $record): array
    {
        return normalize_webinar_record($record);
    }

    /**
     * @param array<string, mixed> $record
     * @return array{record: array<string, mixed>, deficiencies: list<string>}
     */
    public static function validate_record(array $record): array
    {
        return validate_webinar_record($record);
    }

    /**
     * @param array<string, mixed> $record
     * @param array<string, mixed> $step
     * @return array<string, mixed>
     */
    public static function build_request(string $op, array $record, array $step): array
    {
        return build_webinar_request($op, $record, $step);
    }
}
