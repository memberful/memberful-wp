<?php
/**
 * Paywall builder HTML renderer
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
	 * Flag to determine if we should load paywall styles.
	 *
	 * @var bool
	 */
	private static $should_print_styles = false;

	/**
	 * Register the actions on plugin load.
	 */
	public static function register(): void {
		add_filter( 'memberful_wp_protect_content', array( __CLASS__, 'protect_content' ) );
		add_action( 'wp_footer', array( __CLASS__, 'maybe_print_styles' ) );
	}

	/**
	 * Conditionally flag paywall loading.
	 *
	 * @param string $content Content.
	 *
	 * @return string
	 */
	public static function protect_content( string $content ): string {
		$config = Memberful_Paywall_Config::get();

		if ( 'builder' === $config['mode'] ) {
			self::$should_print_styles = true;
		}

		return $content;
	}

	/**
	 * Render paywall styles.
	 */
	public static function maybe_print_styles(): void {
		if ( ! self::$should_print_styles ) {
			return;
		}

		wp_enqueue_style(
			'memberful-paywall',
			MEMBERFUL_URL . '/stylesheets/paywall.css',
			array(),
			MEMBERFUL_VERSION
		);
	}

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

		return sprintf(
			'<div class="memberful-paywall memberful-paywall--%1$s" style="%2$s">%3$s</div>',
			esc_attr( $layout ),
			esc_attr( self::wrapper_style( $config ) ),
			self::$method( $config )
		);
	}

	/**
	 * Render the "simple" layout — minimal text + CTA on a transparent band.
	 *
	 * @param array $config Sanitized config.
	 *
	 * @return string
	 */
	private static function render_simple( array $config ): string {
		return '<div class="memberful-paywall__inner">'
			. self::heading_block( $config )
			. self::subheading_block( $config )
			. self::features_block( $config )
			. '<div class="memberful-paywall__actions">' . self::primary_cta( $config ) . '</div>'
			. self::sign_in_prompt( $config )
			. '</div>';
	}

	/**
	 * Render the "card" layout — centred white card with lock badge.
	 *
	 * @param array $config Sanitized config.
	 *
	 * @return string
	 */
	private static function render_card( array $config ): string {
		return '<div class="memberful-paywall__inner">'
			. '<div class="memberful-paywall__card">'
			. self::lock_badge()
			. self::heading_block( $config )
			. self::subheading_block( $config )
			. self::features_block( $config )
			. '<div class="memberful-paywall__actions">' . self::primary_cta( $config ) . '</div>'
			. self::sign_in_prompt( $config )
			. '</div>'
			. '</div>';
	}

	/**
	 * Render the "banner" layout — full-width dark band.
	 *
	 * @param array $config Sanitized config.
	 *
	 * @return string
	 */
	private static function render_banner( array $config ): string {
		return '<div class="memberful-paywall__inner">'
			. self::heading_block( $config )
			. self::subheading_block( $config )
			. self::features_block( $config )
			. '<div class="memberful-paywall__actions">' . self::primary_cta( $config ) . '</div>'
			. self::sign_in_prompt( $config )
			. '</div>';
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
	 * Subheading paragraph, or empty when blank.
	 *
	 * @param array $config Sanitized config.
	 *
	 * @return string
	 */
	private static function subheading_block( array $config ): string {
		if ( '' === $config['subheading'] ) {
			return '';
		}

		return sprintf(
			'<p class="memberful-paywall__subheading">%s</p>',
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
	 * "Already a subscriber? Sign in" text prompt shown under every layout's CTA.
	 *
	 * @param array $config Sanitized config.
	 *
	 * @return string
	 */
	private static function sign_in_prompt( array $config ): string {
		return sprintf(
			'<p class="memberful-paywall__signin">%s <a class="memberful-paywall__signin-link" href="%s">%s</a></p>',
			esc_html__( 'Already a subscriber?', 'memberful' ),
			esc_url( self::sign_in_url( $config ) ),
			esc_html__( 'Sign in', 'memberful' )
		);
	}

	/**
	 * Circular lock badge shown at the top of the card layout.
	 *
	 * @return string
	 */
	private static function lock_badge(): string {
		return '<span class="memberful-paywall__lock" aria-hidden="true">'
			. '<svg width="20" height="20" viewBox="0 0 24 24" focusable="false">'
			. '<path fill="currentColor" d="M12 2a5 5 0 00-5 5v3H6a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2v-8a2 2 0 00-2-2h-1V7a5 5 0 00-5-5zm-3 8V7a3 3 0 016 0v3H9z"/>'
			. '</svg>'
			. '</span>';
	}

	/**
	 * Wrapper inline style carrying the brand colour and button radius custom properties.
	 *
	 * @param array $config Sanitized config.
	 *
	 * @return string
	 */
	private static function wrapper_style( array $config ): string {
		$parts = array( '--mf-radius:' . self::button_radius( $config['button_shape'] ) );

		if ( ! empty( $config['brand_color'] ) ) {
			$parts[] = '--mf-brand:' . $config['brand_color'];
		}

		return implode( ';', $parts ) . ';';
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

Memberful_Paywall_Renderer::register();
