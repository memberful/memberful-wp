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
  }

  public function is_installed() {
    return is_plugin_active( 'adthrive-ads/adthrive-ads.php' );
  }

  public function get_name() {
    return 'Raptive Ads';
  }

  public function get_identifier() {
    return 'raptive-ads';
  }

  public function disable_ads_for_user($user_id) {
    add_filter( 'body_class', array( $this, 'disable_ads_body_class' ) );
  }

  public function apply_ad_controls_for_user($user_id) {
    if( $this->should_disable_ads_for_user( $user_id ) ) {
      $this->disable_ads_for_user( $user_id );
    }
  }

  public function should_disable_ads_for_user($user_id) {
    // consider the user's plans and the ad provider's settings to determine if ads should be disabled.
    return false;
  }

  public function get_ad_provider_settings() {
    return array(
      'disabled_plans' => array(),
      'disable_for_all_subscribers' => false,
    );
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
