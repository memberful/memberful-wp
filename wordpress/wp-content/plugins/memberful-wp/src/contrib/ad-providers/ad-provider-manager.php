<?php
/**
 * Manager class for all ad providers.
 *
 * @since 1.78.0
 * @package memberful-wp
 */

 require_once MEMBERFUL_DIR . '/src/contrib/ad-providers/base-ad-provider.php';

/**
 * Central registry and coordinator for all ad providers.
 *
 * @package memberful-wp
 * @since 1.78.0
 */
class Memberful_Wp_Integration_Ad_Provider_Manager {

  /**
   * The instance of the ad provider manager.
   *
   * @var Memberful_Wp_Integration_Ad_Provider_Manager
   */
  protected static $_instance;

  /**
   * The array of registered ad providers.
   *
   * @var array
   * @see Memberful_Wp_Integration_Ad_Provider_Base
   */
  protected $providers = array();

  /**
   * @return Memberful_Wp_Integration_Ad_Provider_Manager The instance of the ad provider manager.
   */
  public static function instance() {
    if( self::$_instance === null ) {
      self::$_instance = new self();
    }

    return self::$_instance;
  }

  /**
   * Constructor.
   */
  private function __construct() {
  }

  /**
   * Initialize the ad provider manager.
   */
  public function init() {
    /**
     * Action to register ad providers.
     *
     * All ad providers should be registered using this action.
     *
     * @param Memberful_Wp_Integration_Ad_Provider_Manager $this The ad provider manager instance.
     * @return void
     */
    do_action( 'memberful_ad_provider_register_providers', $this );

    // Once all providers are registered, apply ad controls for users.
    $this->apply_ad_controls_for_user();
  }

  /**
   * Register a new ad provider.
   *
   * @param Memberful_Wp_Integration_Ad_Provider_Base $provider The ad provider to register.
   */
  public function register_provider( $provider ) {
    $this->providers[ $provider->get_identifier() ] = $provider;
  }

  /**
   * Get all registered ad providers (active and inactive).
   *
   * @return array An array of all registered ad providers.
   */
  public function get_all_providers() {
    /**
     * Filter all providers (active and inactive).
     *
     * @param array $providers The registered ad providers.
     * @return array The filtered registered ad providers.
     */
    $providers = apply_filters( 'memberful_ad_provider_all_providers', $this->providers );

    return $providers;
  }

  /**
   * Get a registered ad provider by identifier.
   *
   * @param string $identifier The identifier of the ad provider.
   * @return Memberful_Wp_Integration_Ad_Provider_Base|null The ad provider, or null if not found.
   */
  public function get_provider( $identifier ) {
    if ( ! isset( $this->providers[ $identifier ] ) ) {
      return null;
    }

    return $this->providers[ $identifier ] ?? null;
  }

  /**
   * Get all detected/active ad providers.
   *
   * @return array An array of detected ad providers.
   */
  public function get_detected_providers() {
    $detected_providers = array_filter( $this->providers, function( $provider ) {
      return $provider->is_installed();
    } );

    /**
     * Filter the detected providers.
     *
     * @param array $detected_providers The detected providers.
     * @return array The filtered detected providers.
     */
    $detected_providers = apply_filters( 'memberful_ad_provider_detected_providers', $detected_providers );

    return $detected_providers;
  }

  /**
   * Check if ads should be disabled for a user.
   *
   * @param int $user_id The ID of the user to check.
   * @param string $provider_id The identifier of the ad provider.
   * @return bool True if ads should be disabled, false otherwise.
   */
  public function should_disable_ads_for_user( $user_id, $provider_id ) {
    $provider = $this->get_provider( $provider_id );
    if ( ! $provider ) {
      return false;
    }

    return $provider->should_disable_ads_for_user( $user_id );
  }

  /**
   * Apply ad controls for a user.
   *
   * @param int $user_id The ID of the user to apply ad controls for.
   */
  public function apply_ad_controls_for_user() {
    $user_id = get_current_user_id();

    if ( ! $user_id ) {
      return;
    }

    /** @var Memberful_Wp_Integration_Ad_Provider_Base $provider */
    foreach ( $this->get_detected_providers() as $provider ) {

      /**
       * Filter if ad controls should be applied for a provider.
       *
       * @param bool $should_apply Whether to apply ad controls for the provider, or null to use default logic.
       * @param Memberful_Wp_Integration_Ad_Provider_Base $provider The ad provider.
       * @param int $user_id The ID of the user to apply ad controls for.
       * @return bool Whether to apply ad controls for the provider.
       */
      if ( ! apply_filters( 'memberful_ad_provider_should_apply_controls_for_provider', true, $provider, $user_id ) ) {
        continue;
      }

      $provider->apply_ad_controls_for_user( $user_id );
    }
  }
}
