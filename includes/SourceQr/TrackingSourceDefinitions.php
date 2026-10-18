<?php

/**
 * Resolve tracking source definition rows for Source QR catalog REST.
 *
 * @package S2J_Webinar
 */

declare(strict_types=1);

namespace S2J\Webinar\SourceQr;

use S2J\Webinar\Constants;

final class TrackingSourceDefinitions
{
    /**
     * @return list<array{source_name: string, label: string}>
     */
    public static function for_event(int $post_id, string $provider): array
    {
        $defaults = self::default_for_provider($provider);
        $sources  = apply_filters('s2j_webinar_tracking_source_definitions', $defaults, $provider, $post_id);

        return self::normalize_list(is_array($sources) ? $sources : array());
    }

    /**
     * @return list<array{source_name: string, label: string}>
     */
    private static function default_for_provider(string $provider): array
    {
        if ($provider === Constants::DEFAULT_PROVIDER) {
            return array(
                array(
                    'source_name' => 'web',
                    'label'       => __('Web', 's2j-webinar'),
                ),
                array(
                    'source_name' => 'sns',
                    'label'       => __('SNS', 's2j-webinar'),
                ),
                array(
                    'source_name' => 'other',
                    'label'       => __('Other', 's2j-webinar'),
                ),
            );
        }

        return array();
    }

    /**
     * @param list<mixed> $sources
     * @return list<array{source_name: string, label: string}>
     */
    private static function normalize_list(array $sources): array
    {
        $out = array();
        foreach ($sources as $row) {
            if (! is_array($row)) {
                continue;
            }
            $source_name = isset($row['source_name']) ? trim(sanitize_text_field((string) $row['source_name'])) : '';
            if ($source_name === '') {
                continue;
            }
            $label   = isset($row['label']) ? sanitize_text_field((string) $row['label']) : '';
            $out[] = array(
                'source_name' => $source_name,
                'label'       => $label,
            );
        }

        return $out;
    }
}
