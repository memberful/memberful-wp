<?php
/**
 * Raptive Ads integration.
 *
 * @package memberful-wp
 */

/**
 * Raptive Ads provider class.
 */
class Memberful_Wp_Integration_Ad_Provider_Raptive extends Memberful_Wp_Integration_Ad_Provider_Base {

  public function __construct() {
    parent::__construct();
    $this->name = 'Raptive Ads';
    $this->identifier = 'raptive-ads';
  }

  public function is_installed() {
    return is_plugin_active( 'adthrive-ads/adthrive-ads.php' );
  }

  public function get_name() {
    return $this->name;
  }

  public function get_identifier() {
    return $this->identifier;
  }

  public function disable_ads_for_user($user_id) {
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
  public function disable_ads_body_class($classes) {
    $classes[] = 'adthrive-disable-all';
    return $classes;
  }
}
