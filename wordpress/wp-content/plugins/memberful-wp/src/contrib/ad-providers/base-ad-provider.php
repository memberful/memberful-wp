<?php
/**
 * Base class for ad provider integrations to extend from.
 *
 * @package memberful-wp
 */

/**
 * Abstract base class defining the interface for all ad providers.
 */
abstract class Memberful_Wp_Integration_Ad_Provider_Base {

  /**
   * The human-readable name of the ad provider.
   *
   * @var string The human-readable name of the ad provider.
   */
  protected $name;

  /**
   * The identifier of the ad provider.
   *
   * @var string The identifier of the ad provider.
   */
  protected $identifier;

  /**
   * Constructor.
   */
  public function __construct() {
    $this->init_hooks();
  }

  /**
   * Initialize the hooks for the ad provider (if any).
   *
   * This is run at constructor time.
   */
  public function init_hooks() {}

  /**
   * Check if the ad provider is installed.
   *
   * @return bool True if the ad provider is installed, false otherwise.
   */
  public function is_installed() {}

  /**
   * Get the human-readable name of the ad provider.
   *
   * @return string The human-readable name of the ad provider.
   */
  public function get_name() {}

  /**
   * Get the identifier of the ad provider.
   *
   * @return string The identifier of the ad provider.
   */
  public function get_identifier() {}

  /**
   * Disable ads for a user.
   *
   * @param int $user_id The ID of the user to disable ads for.
   */
  public function disable_ads_for_user($user_id) {}

  /**
   * Get the detection methods for the ad provider.
   *
   * @return array An array of detection methods.
   */
  public function get_detection_methods() {}

  /**
   * Apply ad controls for a user.
   *
   * Either disable or enable this provider's ads for a user.
   *
   * @param int $user_id The ID of the user to apply ad controls for.
   */
  public function apply_ad_controls_for_user($user_id) {
    if ( $this->should_disable_ads_for_user( $user_id ) ) {
      $this->disable_ads_for_user( $user_id );
    }
  }

  /**
   * Check if ads should be disabled for a user.
   *
   * @param int $user_id The ID of the user to check.
   * @return bool True if ads should be disabled, false otherwise.
   */
  public function should_disable_ads_for_user($user_id) {
    $settings = $this->get_ad_provider_settings();

    // Provider is not enabled.
    if ( empty( $settings ) || ! isset( $settings['enabled'] ) || $settings['enabled'] !== true ) {
      return false;
    }

    // Disable for all subscribers.
    if ( isset( $settings['disable_for_all_subscribers'] ) && $settings['disable_for_all_subscribers'] === true && is_subscribed_to_any_memberful_plan( $user_id ) ) {
      return true;
    }

    // Disable for logged in users.
    if ( isset( $settings['disable_for_logged_in'] ) && $settings['disable_for_logged_in'] === true && is_user_logged_in() ) {
      return true;
    }

    // Disable for specific plans.
    if ( isset( $settings['disabled_plans'] ) && ! empty( $settings['disabled_plans'] ) ) {
      $user_plans = memberful_wp_user_plans_subscribed_to( $user_id );
      return count( array_intersect( $user_plans, $settings['disabled_plans'] ) ) > 0;
    }

    return false;
  }

  /**
   * Get the settings for the ad provider.
   *
   * e.g. what plans should ads be disabled for, etc.
   *
   * @return array The settings for the ad provider.
   */
  public function get_ad_provider_settings() {
    $stored_settings = get_option( 'memberful_ad_provider_settings', array() );
    $settings = isset( $stored_settings[ $this->get_identifier() ] ) && is_array( $stored_settings[ $this->get_identifier() ] )
      ? $stored_settings[ $this->get_identifier() ]
      : array();

    return $settings;
  }
}
