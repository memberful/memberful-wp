<?php
/**
 * Admin-ajax preview endpoint for the paywall builder
 *
 * @package memberful-wp
 */

/**
 * Class Memberful_Paywall_Preview.
 */
class Memberful_Paywall_Preview {
	const ACTION    = 'memberful_paywall_preview';
	const NONCE_KEY = 'memberful_paywall_preview';

	/**
	 * Register the admin-ajax handler on plugin load.
	 */
	public static function register(): void {
		add_action( 'wp_ajax_' . self::ACTION, array( __CLASS__, 'handle' ) );
	}

	/**
	 * Admin-ajax handler: sanitize the posted config, render HTML, return document.
	 */
	public static function handle(): void {
		check_ajax_referer( self::NONCE_KEY, 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
		}

		$raw = filter_input( INPUT_POST, 'config', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
		$raw = is_array( $raw ) ? $raw : array();

		$config = Memberful_Paywall_Sanitizer::sanitize( $raw, Memberful_Paywall_Config::defaults() );

		wp_send_json_success( array( 'html' => self::document( $config ) ) );
	}

	/**
	 * Wrap the rendered paywall HTML in a minimal document suitable for an iframe.
	 *
	 * @param array $config Sanitized config.
	 *
	 * @return string
	 */
	public static function document( array $config ): string {
		// The builder preview has no live metered context, so seed a sample limit to show the free-view counter.
		add_filter( 'memberful_paywall_free_view_limit', array( __CLASS__, 'sample_free_view_limit' ) );
		$body = Memberful_Paywall_Renderer::render( $config, false );
		remove_filter( 'memberful_paywall_free_view_limit', array( __CLASS__, 'sample_free_view_limit' ) );

		$paywall_css = add_query_arg( 'ver', MEMBERFUL_VERSION, plugins_url( 'stylesheets/paywall.css', MEMBERFUL_PLUGIN_FILE ) );
		$theme_css   = get_stylesheet_uri();

		$links = '';
		if ( ! empty( $theme_css ) ) {
			$links .= sprintf( '<link rel="stylesheet" href="%s">', esc_url( $theme_css ) );
		}

		$links .= self::font_faces();
		$links .= self::global_styles();
		$links .= sprintf( '<link rel="stylesheet" href="%s">', esc_url( $paywall_css ) );

		$teaser_class = 'memberful-global-teaser-content';
		$teaser       = sprintf(
			'<div class="%1$s" aria-hidden="true"><p>%2$s</p></div>',
			esc_attr( $teaser_class ),
			esc_html__( 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nulla vitae urna id quam faucibus gravida ac sed ipsum. Quisque eget velit dictum leo tempor bibendum nec sed odio.', 'memberful' )
		);

		$styles = 'html,body{background:#fff;color:#1b1b1b;font-size:16px;line-height:1.6;margin:0;overflow:hidden;}'
			. '.memberful-global-teaser-content{padding:24px 24px 0;}'
			. '.memberful-global-teaser-content p{margin:0; padding-bottom: 1rem;}'
			. '.memberful-paywall--card .memberful-paywall__inner {padding:0;}';

		return '<!doctype html>'
			. '<html lang="' . esc_attr( get_bloginfo( 'language' ) ) . '">'
			. '<head>'
			. '<meta charset="utf-8">'
			. '<meta name="viewport" content="width=device-width,initial-scale=1">'
			. $links
			. '<style>' . $styles . '</style>'
			. '</head>'
			. '<body>' . $teaser . $body . '</body>'
			. '</html>';
	}

	/**
	 * Sample free-view limit so the counter renders in the builder preview, which has no live metered context. Keeps
	 * the incoming null while metering is disabled, matching the live paywall, which never shows the counter then.
	 * Uses the higher of the two tier limits so the preview still shows the counter when one tier is set to zero.
	 * Untyped because memberful_paywall_free_view_limit is a public filter and earlier callbacks may pass anything.
	 *
	 * @param int|null $limit Incoming limit.
	 *
	 * @return int|null
	 */
	public static function sample_free_view_limit( $limit ) {
		if ( ! class_exists( 'Memberful_Metering_Config' ) ) {
			return $limit;
		}

		$config = Memberful_Metering_Config::get();

		if ( empty( $config['enabled'] ) ) {
			return $limit;
		}

		return max( (int) $config['anonymous_limit'], (int) $config['registered_limit'] );
	}

	/**
	 * Generate theme.json font-face rules for the iframe preview.
	 *
	 * @return string
	 */
	private static function font_faces(): string {
		if ( ! function_exists( 'wp_print_font_faces' ) ) {
			return '';
		}

		ob_start();
		wp_print_font_faces();

		return (string) ob_get_clean();
	}

	/**
	 * Generate global styles so the preview inherits theme font variables and body typography.
	 *
	 * @return string
	 */
	private static function global_styles(): string {
		if ( ! function_exists( 'wp_get_global_stylesheet' ) ) {
			return '';
		}

		$stylesheet = trim( wp_get_global_stylesheet() );
		if ( '' === $stylesheet ) {
			return '';
		}

		return '<style id="memberful-paywall-preview-global-styles">' . $stylesheet . '</style>';
	}

	/**
	 * AJAX args passed to the builder JS via wp_localize_script.
	 *
	 * @return array
	 */
	public static function script_args(): array {
		return array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'action'  => self::ACTION,
			'nonce'   => wp_create_nonce( self::NONCE_KEY ),
		);
	}
}

Memberful_Paywall_Preview::register();
