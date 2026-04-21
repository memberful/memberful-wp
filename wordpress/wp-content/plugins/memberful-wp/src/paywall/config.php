<?php
/**
 * Paywall builder configuration: defaults, read, and write.
 *
 * @package memberful-wp
 */
class Memberful_Paywall_Config {
	const OPTION_KEY = 'memberful_paywall_config';

	const MODES           = array( 'builder', 'custom_html' );
	const LAYOUTS         = array( 'simple', 'card', 'banner' );
	const HEADING_TAGS    = array( 'h1', 'h2', 'h3' );
	const SUBHEADING_TAGS = array( 'p', 'h3', 'h4' );
	const BUTTON_SHAPES   = array( 'pill', 'rounded', 'square' );

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
