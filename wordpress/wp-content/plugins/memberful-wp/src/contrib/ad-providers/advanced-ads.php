<?php
/**
 * Advanced Ads integration.
 *
 * @since 1.78.0
 * @package memberful-wp
 */

/**
 * Advanced Ads provider class.
 *
 * Disables ads by short-circuiting Advanced Ads display checks.
 *
 * @see Memberful_Wp_Integration_Ad_Provider_Base
 * @package memberful-wp
 * @since 1.78.0
 */
class Memberful_Wp_Integration_Ad_Provider_Advanced_Ads extends Memberful_Wp_Integration_Ad_Provider_Base {

  /**
   * Constructor.
   */
  public function __construct() {
    $this->name       = 'Advanced Ads';
    $this->identifier = 'advanced-ads';
    $this->init_hooks();
  }

  /**
   * {@inheritdoc}
   */
  public function is_installed() {
    if ( ! function_exists( 'is_plugin_active' ) ) {
      require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    return is_plugin_active( 'advanced-ads/advanced-ads.php' );
  }

  /**
   * {@inheritdoc}
   */
  public function get_name() {
    return $this->name;
  }

  /**
   * {@inheritdoc}
   */
  public function get_identifier() {
    return $this->identifier;
  }

  /**
   * Disable ads by failing Advanced Ads display checks.
   *
   * @param int $user_id The ID of the user to disable ads for.
   */
  public function disable_ads_for_user( $user_id ) {
    // Preferred filter from Advanced Ads 2.0.0+.
    add_filter( 'advanced-ads-can-display-ad', array( $this, 'disable_advanced_ads' ), 10, 3 );
  }

  /**
   * Disable Advanced Ads output for the current request.
   *
   * Accepts and ignores additional arguments to stay compatible with
   * filter signature differences across Advanced Ads versions.
   *
   * @param mixed $can_display Current display decision.
   * @param mixed ...$args Additional hook arguments.
   * @return bool Always false.
   */
  public function disable_advanced_ads( $can_display, ...$args ) {
    return false;
  }
}
