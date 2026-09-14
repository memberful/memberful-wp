<?php
/**
 * Sanitization for the paywall builder configuration
 *
 * @package memberful-wp
 */

/**
 * Class Memberful_Paywall_Sanitizer.
 */
class Memberful_Paywall_Sanitizer {
	/**
	 * Sanitize a raw paywall-config input array against a canonical defaults array.
	 *
	 * @param array $input    Raw input (typically $_POST payload shape).
	 * @param array $defaults Canonical defaults from Memberful_Paywall_Config::defaults().
	 *
	 * @return array Sanitized config ready for update_option().
	 */
	public static function sanitize( array $input, array $defaults ): array {
		$input = array_intersect_key( $input, $defaults );
		$clean = $defaults;

		$enums = array(
			'mode'         => Memberful_Paywall_Config::MODES,
			'layout'       => Memberful_Paywall_Config::LAYOUTS,
			'button_shape' => Memberful_Paywall_Config::BUTTON_SHAPES,
		);

		foreach ( $enums as $key => $allowed ) {
			if ( isset( $input[ $key ] ) && in_array( $input[ $key ], $allowed, true ) ) {
				$clean[ $key ] = $input[ $key ];
			}
		}

		foreach ( array( 'heading', 'subheading', 'button_label', 'free_button_label', 'counter_template' ) as $key ) {
			if ( isset( $input[ $key ] ) ) {
				$clean[ $key ] = sanitize_text_field( (string) $input[ $key ] );
			}
		}

		if ( isset( $input['show_counter'] ) ) {
			$clean['show_counter'] = ! empty( $input['show_counter'] );
		}

		if ( isset( $input['features'] ) ) {
			$features = is_array( $input['features'] )
				? $input['features']
				: preg_split( "/\r\n|\n|\r/", (string) $input['features'] );

			$features = array_map( 'sanitize_text_field', (array) $features );
			$features = array_map( 'trim', $features );
			$features = array_values( array_filter( $features, 'strlen' ) );

			$clean['features'] = $features;
		}

		foreach ( array( 'subscribe_url', 'sign_in_url', 'free_button_url' ) as $key ) {
			if ( isset( $input[ $key ] ) ) {
				$clean[ $key ] = esc_url_raw( (string) $input[ $key ] );
			}
		}

		foreach ( array( 'brand_color', 'background_color' ) as $color_key ) {
			if ( isset( $input[ $color_key ] ) ) {
				$color = sanitize_hex_color( (string) $input[ $color_key ] );
				if ( null !== $color && '' !== $color ) {
					$clean[ $color_key ] = $color;
				}
			}
		}

		return $clean;
	}
}
