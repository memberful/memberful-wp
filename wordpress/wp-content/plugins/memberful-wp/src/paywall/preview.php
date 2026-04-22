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
		$body = Memberful_Paywall_Renderer::render( $config );

		$paywall_css = plugins_url( 'stylesheets/paywall.css', MEMBERFUL_PLUGIN_FILE );
		$theme_css   = get_stylesheet_uri();

		$links = sprintf( '<link rel="stylesheet" href="%s">', esc_url( $paywall_css ) );
		if ( ! empty( $theme_css ) ) {
			$links .= sprintf( '<link rel="stylesheet" href="%s">', esc_url( $theme_css ) );
		}

		return '<!doctype html>'
			. '<html lang="' . esc_attr( get_bloginfo( 'language' ) ) . '">'
			. '<head>'
			. '<meta charset="utf-8">'
			. '<meta name="viewport" content="width=device-width,initial-scale=1">'
			. $links
			. '<style>html,body{margin:0;padding:16px;background:#fff;}</style>'
			. '</head>'
			. '<body>' . $body . '</body>'
			. '</html>';
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
