<?php
/**
 * Paywall builder configuration: defaults, read, and write.
 *
 * @package memberful-wp
 */

/**
 * Class Memberful_Paywall_Config
 */
class Memberful_Paywall_Config {
	const OPTION_KEY = 'memberful_paywall_config';

	const MODES         = array( 'builder', 'custom_html' );
	const LAYOUTS       = array( 'inline', 'card' );
	const BUTTON_SHAPES = array( 'pill', 'rounded', 'square' );

	/**
	 * Canonical default configuration shape.
	 *
	 * @return array
	 */
	public static function defaults(): array {
		return array(
			'mode'              => 'builder',
			'layout'            => 'card',
			'heading'           => __( 'Subscribe to keep reading', 'memberful' ),
			'subheading'        => __( 'This post is for paying subscribers.', 'memberful' ),
			'features'          => array(),
			'button_label'      => __( 'Subscribe', 'memberful' ),
			'subscribe_url'     => '',
			'free_button_label' => __( 'Create a free account', 'memberful' ),
			'free_button_url'   => '',
			'sign_in_url'       => '',
			'brand_color'       => '',
			'background_color'  => '',
			'button_shape'      => 'square',
		);
	}

	/**
	 * Read the stored config merged over defaults.
	 *
	 * On sites with legacy custom HTML in memberful_global_marketing_content and no stored builder config yet, the
	 * default mode swaps to custom_html so the existing content keeps rendering untouched. Once the user saves any
	 * config, the stored value wins and this check short-circuits.
	 *
	 * @return array
	 */
	public static function get(): array {
		$stored = get_option( self::OPTION_KEY, array() );

		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		$defaults = self::defaults();
		if ( empty( $stored ) && self::has_legacy_content() ) {
			$defaults['mode'] = 'custom_html';
		}

		return wp_parse_args( $stored, $defaults );
	}

	/**
	 * Whether the legacy marketing content option is populated.
	 *
	 * @return bool
	 */
	private static function has_legacy_content(): bool {
		return '' !== trim( (string) get_option( 'memberful_global_marketing_content' ) );
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
