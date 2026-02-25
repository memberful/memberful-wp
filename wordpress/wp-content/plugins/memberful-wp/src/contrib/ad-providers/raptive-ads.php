<?php
/**
 * Raptive Ads integration.
 *
 * @since 1.78.0
 * @package memberful-wp
 */

/**
 * Raptive Ads provider class.
 *
 * @see Memberful_Wp_Integration_Ad_Provider_Base
 * @package memberful-wp
 * @since 1.78.0
 */
class Memberful_Wp_Integration_Ad_Provider_Raptive extends Memberful_Wp_Integration_Ad_Provider_Base {

  public function __construct() {
    $this->name = 'Raptive Ads';
    $this->identifier = 'raptive-ads';
    $this->init_hooks();
  }

  public function is_installed() {
    if ( ! function_exists( 'is_plugin_active' ) ) {
      require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    return is_plugin_active( 'adthrive-ads/adthrive-ads.php' );
  }

  public function get_name() {
    return $this->name;
  }

  public function get_identifier() {
    return $this->identifier;
  }

  public function disable_ads_for_user( $user_id ) {
    add_filter( 'body_class', array( $this, 'disable_ads_body_class' ) );
  }

  /**
   * Disable ads for the Raptive Ads provider.
   *
   * Uses the adthrive-disable-all body class to disable ads.
   *
   * @see \AdThrive_Ads\Components\Ads\Main::body_class()
   *
   * @param array $classes The body classes.
   * @return array The body classes.
   */
  public function disable_ads_body_class( $classes ) {
    $classes[] = 'adthrive-disable-all';
    return $classes;
  }
}
