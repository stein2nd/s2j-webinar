<?php
/**
 * Map service deficiency codes to i18n messages (admin_ui_spec.md).
 *
 * @package S2J_Webinar
 */

declare(strict_types=1);

namespace S2J\Webinar\Messages;

use S2J\Webinar\Provider\WebinarProvider;

final class DeficiencyMessages {

	/**
	 * @param list<string> $codes
	 * @return list<string>
	 */
	public static function translate_codes( array $codes ): array {
		$out = array();
		foreach ( $codes as $code ) {
			$out[] = self::translate_code( (string) $code );
		}
		return $out;
	}

	public static function translate_code( string $code ): string {
		$provider = WebinarProvider::label();
		$map      = array(
			'topic_empty'            => __( 'Event title is required.', 's2j-webinar' ),
			'start_at_empty'         => __( 'Event start date and time are required.', 's2j-webinar' ),
			'timezone_empty'         => __( 'Event timezone is required.', 's2j-webinar' ),
			'duration_invalid'       => __( 'Event duration must be at least one minute.', 's2j-webinar' ),
			'panelists_empty'        => __( 'Add at least one panelist before syncing.', 's2j-webinar' ),
			'panelist_incomplete'    => __( 'Each panelist needs a valid email address.', 's2j-webinar' ),
			'panelist_email_invalid' => __( 'A panelist email address is invalid.', 's2j-webinar' ),
			'webinar_id_empty'       => \sprintf(
				/* translators: %s: webinar provider name, e.g. Zoom */
				__( 'This event is not linked to a %s webinar yet.', 's2j-webinar' ),
				$provider
			),
			'provider_unsupported'   => __( 'This provider is not supported.', 's2j-webinar' ),
		);

		if ( isset( $map[ $code ] ) ) {
			return $map[ $code ];
		}

		return \sprintf(
			/* translators: %s: webinar provider name, e.g. Zoom */
			__( 'Could not sync. Check event fields and %s connection.', 's2j-webinar' ),
			$provider
		);
	}
}
