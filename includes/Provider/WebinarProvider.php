<?php

/**
 * Site-wide webinar provider slug and display label (Settings → S2J Webinar).
 *
 * @package S2J_Webinar
 */

declare(strict_types=1);

namespace S2J\Webinar\Provider;

use S2J\Webinar\Constants;

final class WebinarProvider
{
    public static function slug(): string
    {
        if (\function_exists('get_option')) {
            $slug = \get_option(Constants::OPTION_WEBINAR_PROVIDER, Constants::DEFAULT_PROVIDER);
            if (\is_string($slug) && $slug !== '') {
                return $slug;
            }
        }
        return Constants::DEFAULT_PROVIDER;
    }

    public static function label(): string
    {
        $labels = array(
            'zoom' => __('Zoom', 's2j-webinar'),
        );
        $slug = self::slug();
        return $labels[ $slug ] ?? \ucfirst($slug);
    }
}
