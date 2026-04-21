<?php
/**
 * Paywall builder configuration: defaults, read, and write.
 *
 * @package memberful-wp
 */
class Memberful_Paywall_Config {
	const OPTION_KEY      = 'memberful_paywall_config';
	const LEGACY_FLAG_KEY = 'memberful_paywall_legacy_detected';

	/**
	 * Canonical default configuration shape.
	 *
	 * @return array
	 */
	public static function defaults(): array {
		return array(
			'mode'           => 'builder',
			'layout'         => 'card',
			'heading'        => esc_html__( 'Subscribe to keep reading', 'memberful' ),
			'heading_tag'    => 'h2',
			'subheading'     => esc_html__( 'This post is for paying subscribers.', 'memberful' ),
			'subheading_tag' => 'p',
			'features'       => array(),
			'button_label'   => esc_html__( 'Subscribe', 'memberful' ),
			'subscribe_url'  => '',
			'sign_in_url'    => '',
			'brand_color'    => '#2f80ed',
			'button_shape'   => 'rounded',
			'custom_css'     => '',
		);
	}

	/**
	 * Read the stored config merged over defaults.
	 *
	 * @return array
	 */
	public static function get(): array {
		$stored = get_option( self::OPTION_KEY, array() );

		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		return wp_parse_args( $stored, self::defaults() );
	}

	/**
	 * Validate, sanitize, and persist a config payload.
	 *
	 * @param array $input Raw input (typically from the options form).
	 *
	 * @return bool True when the option was updated, false when unchanged or on failure.
	 */
	public static function save( array $input ): bool {
		$clean = Memberful_Paywall_Sanitizer::sanitize( $input, self::defaults() );

		return update_option( self::OPTION_KEY, $clean );
	}
}
