<?php
/**
 * Paywall builder HTML renderer.
 *
 * Produces the markup that backs the global paywall content and the live
 * admin preview. Safe to call with any array shape — unknown keys fall back
 * to Memberful_Paywall_Config::defaults().
 *
 * @package memberful-wp
 */

/**
 * Renders a paywall config array to HTML.
 */
class Memberful_Paywall_Renderer {
	/**
	 * Render a paywall config to HTML.
	 *
	 * @param array $config Config shape from Memberful_Paywall_Config::get().
	 *
	 * @return string
	 */
	public static function render( array $config ): string {
		$config = wp_parse_args( $config, Memberful_Paywall_Config::defaults() );

		$layout = in_array( $config['layout'], Memberful_Paywall_Config::LAYOUTS, true ) ? $config['layout'] : 'card';
		$method = 'render_' . $layout;

		$body = self::$method( $config );

		return sprintf(
			'<div class="memberful-paywall memberful-paywall--%s" style="%s">%s</div>%s',
			esc_attr( $layout ),
			esc_attr( self::wrapper_style( $config ) ),
			$body,
			self::custom_css_block( $config )
		);
	}

	/**
	 * Render the "simple" layout.
	 *
	 * @param array $config Sanitized config.
	 *
	 * @return string
	 */
	private static function render_simple( array $config ): string {
		return self::heading_block( $config )
			. self::subheading_block( $config )
			. self::features_block( $config )
			. '<div class="memberful-paywall__actions">' . self::primary_cta( $config ) . '</div>';
	}

	/**
	 * Render the "card" layout.
	 *
	 * @param array $config Sanitized config.
	 *
	 * @return string
	 */
	private static function render_card( array $config ): string {
		return '<div class="memberful-paywall__card">'
			. self::heading_block( $config )
			. self::subheading_block( $config )
			. self::features_block( $config )
			. '<div class="memberful-paywall__actions">'
			. self::primary_cta( $config )
			. self::secondary_cta( $config )
			. '</div>'
			. '</div>';
	}

	/**
	 * Render the "banner" layout.
	 *
	 * @param array $config Sanitized config.
	 *
	 * @return string
	 */
	private static function render_banner( array $config ): string {
		return '<div class="memberful-paywall__banner-text">'
			. self::heading_block( $config )
			. self::subheading_block( $config )
			. self::features_block( $config )
			. '</div>'
			. '<div class="memberful-paywall__actions">' . self::primary_cta( $config ) . '</div>';
	}

	/**
	 * Heading element with the configured tag.
	 *
	 * @param array $config Sanitized config.
	 *
	 * @return string
	 */
	private static function heading_block( array $config ): string {
		$tag = in_array( $config['heading_tag'], Memberful_Paywall_Config::HEADING_TAGS, true ) ? $config['heading_tag'] : 'h2';
		return sprintf(
			'<%1$s class="memberful-paywall__heading">%2$s</%1$s>',
			tag_escape( $tag ),
			esc_html( $config['heading'] )
		);
	}

	/**
	 * Subheading element with the configured tag, or empty when blank.
	 *
	 * @param array $config Sanitized config.
	 *
	 * @return string
	 */
	private static function subheading_block( array $config ): string {
		if ( '' === $config['subheading'] ) {
			return '';
		}

		$tag = in_array( $config['subheading_tag'], Memberful_Paywall_Config::SUBHEADING_TAGS, true ) ? $config['subheading_tag'] : 'p';
		return sprintf(
			'<%1$s class="memberful-paywall__subheading">%2$s</%1$s>',
			tag_escape( $tag ),
			esc_html( $config['subheading'] )
		);
	}

	/**
	 * Feature list with inline check icons, or empty when no features.
	 *
	 * @param array $config Sanitized config.
	 *
	 * @return string
	 */
	private static function features_block( array $config ): string {
		if ( empty( $config['features'] ) ) {
			return '';
		}

		$items = '';
		foreach ( $config['features'] as $feature ) {
			$items .= '<li>' . self::check_icon() . '<span>' . esc_html( $feature ) . '</span></li>';
		}

		return '<ul class="memberful-paywall__features">' . $items . '</ul>';
	}

	/**
	 * Primary subscribe CTA anchor.
	 *
	 * @param array $config Sanitized config.
	 *
	 * @return string
	 */
	private static function primary_cta( array $config ): string {
		return sprintf(
			'<a class="memberful-paywall__button memberful-paywall__button--primary" href="%s">%s</a>',
			esc_url( self::subscribe_url( $config ) ),
			esc_html( $config['button_label'] )
		);
	}

	/**
	 * Secondary sign-in CTA anchor (card layout only).
	 *
	 * @param array $config Sanitized config.
	 *
	 * @return string
	 */
	private static function secondary_cta( array $config ): string {
		return sprintf(
			'<a class="memberful-paywall__button memberful-paywall__button--secondary" href="%s">%s</a>',
			esc_url( self::sign_in_url( $config ) ),
			esc_html__( 'Sign in', 'memberful' )
		);
	}

	/**
	 * Wrapper inline style carrying the brand colour and button radius custom properties.
	 *
	 * @param array $config Sanitized config.
	 *
	 * @return string
	 */
	private static function wrapper_style( array $config ): string {
		return sprintf(
			'--mf-brand:%s;--mf-radius:%s;',
			$config['brand_color'],
			self::button_radius( $config['button_shape'] )
		);
	}

	/**
	 * Map the button-shape enum to a CSS radius.
	 *
	 * @param string $shape One of the `button_shape` enum values.
	 *
	 * @return string
	 */
	private static function button_radius( string $shape ): string {
		switch ( $shape ) {
			case 'pill':
				return '999px';
			case 'square':
				return '0';
			case 'rounded':
			default:
				return '8px';
		}
	}

	/**
	 * Custom CSS <style> tag, or empty when no CSS configured.
	 *
	 * @param array $config Sanitized config.
	 *
	 * @return string
	 */
	private static function custom_css_block( array $config ): string {
		$css = trim( wp_strip_all_tags( (string) $config['custom_css'] ) );
		if ( '' === $css ) {
			return '';
		}
		return '<style>' . $css . '</style>';
	}

	/**
	 * Resolve the subscribe URL, falling back to the Memberful registration page.
	 *
	 * @param array $config Sanitized config.
	 *
	 * @return string
	 */
	private static function subscribe_url( array $config ): string {
		return ! empty( $config['subscribe_url'] ) ? $config['subscribe_url'] : memberful_registration_page_url();
	}

	/**
	 * Resolve the sign-in URL, falling back to the Memberful sign-in endpoint.
	 *
	 * @param array $config Sanitized config.
	 *
	 * @return string
	 */
	private static function sign_in_url( array $config ): string {
		return ! empty( $config['sign_in_url'] ) ? $config['sign_in_url'] : memberful_sign_in_url();
	}

	/**
	 * Inline check-mark SVG used in the features list.
	 *
	 * @return string
	 */
	private static function check_icon(): string {
		return '<svg class="memberful-paywall__check" width="16" height="16" viewBox="0 0 16 16" aria-hidden="true" focusable="false">'
			. '<path fill="currentColor" d="M13.485 4.515a1 1 0 0 0-1.414 0L6.5 10.086 3.929 7.515a1 1 0 1 0-1.414 1.414l3.278 3.278a1 1 0 0 0 1.414 0l6.278-6.278a1 1 0 0 0 0-1.414z"/>'
			. '</svg>';
	}
}
