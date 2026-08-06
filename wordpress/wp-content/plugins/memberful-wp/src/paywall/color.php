<?php
/**
 * Colour helpers for the paywall renderer.
 *
 * @package memberful-wp
 */

/**
 * Class Memberful_Paywall_Color.
 */
class Memberful_Paywall_Color {
	const TEXT_DARK           = '#1a1a1a';
	const TEXT_LIGHT          = '#ffffff';
	const LUMINANCE_THRESHOLD = 0.179;

	/**
	 * WCAG relative luminance for a hex colour. Returns 0.0 for unparseable input.
	 *
	 * @param string $hex Hex colour, 3- or 6-digit, with or without leading hash.
	 *
	 * @return float
	 */
	public static function relative_luminance( string $hex ): float {
		$rgb = self::hex_to_rgb( $hex );
		if ( null === $rgb ) {
			return 0.0;
		}

		$channels = array_map(
			static function ( int $value ) {
				$normalised = $value / 255;
				return $normalised <= 0.03928 ? $normalised / 12.92 : pow( ( $normalised + 0.055 ) / 1.055, 2.4 );
			},
			$rgb
		);

		return $channels[0] * 0.2126 + $channels[1] * 0.7152 + $channels[2] * 0.0722;
	}

	/**
	 * Pick a legible text colour for a given background hex, using the WCAG luminance threshold
	 * where black and white reach equal contrast (~0.179).
	 *
	 * @param string $hex Background hex.
	 *
	 * @return string Hex string for the text colour.
	 */
	public static function contrast_text_color( string $hex ): string {
		return self::relative_luminance( $hex ) > self::LUMINANCE_THRESHOLD ? self::TEXT_DARK : self::TEXT_LIGHT;
	}

	/**
	 * Parse a 3- or 6-digit hex colour into [r, g, b] of 0–255 integers.
	 *
	 * @param string $hex Hex colour input.
	 *
	 * @return array{0:int,1:int,2:int}|null
	 */
	private static function hex_to_rgb( string $hex ): ?array {
		$hex = ltrim( trim( $hex ), '#' );

		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}

		if ( 6 !== strlen( $hex ) || ! ctype_xdigit( $hex ) ) {
			return null;
		}

		return array(
			(int) hexdec( substr( $hex, 0, 2 ) ),
			(int) hexdec( substr( $hex, 2, 2 ) ),
			(int) hexdec( substr( $hex, 4, 2 ) ),
		);
	}
}
