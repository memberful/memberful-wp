<?php
/**
 * Manager class for all ad providers.
 *
 * @package memberful-wp
 */

/**
 * Central registry and coordinator for all ad providers.
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
  public function __construct() {
    // Auto-register providers on plugins_loaded hook.
    add_action( 'plugins_loaded', array( $this, 'auto_register_providers' ) );
  }

  /**
   * Auto-register providers on plugins_loaded hook.
   */
  public function auto_register_providers() {
    // Manually register providers here for auto registration.
    $this->register_provider( new Memberful_Wp_Integration_Ad_Provider_Raptive() );
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
    return $this->providers;
  }

  /**
   * Get a registered ad provider by identifier.
   *
   * @param string $identifier The identifier of the ad provider.
   * @return Memberful_Wp_Integration_Ad_Provider_Base|null The ad provider, or null if not found.
   */
  public function get_provider( $identifier ) {
    if( ! isset( $this->providers[$identifier] ) ) {
      return null;
    }

    return $this->providers[$identifier] ?? null;
  }

  /**
   * Get all detected/active ad providers.
   *
   * @return array An array of detected ad providers.
   */
  public function get_detected_providers() {
    return array_filter( $this->providers, function( $provider ) {
      return $provider->is_installed();
    } );
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
    if( ! $provider ) {
      return false;
    }

    return $provider->should_disable_ads_for_user( $user_id );
  }

  /**
   * Apply ad controls for a user.
   *
   * @param int $user_id The ID of the user to apply ad controls for.
   */
  public function apply_ad_controls_for_user( $user_id ) {
    /** @var Memberful_Wp_Integration_Ad_Provider_Base $provider */
    foreach( $this->get_detected_providers() as $provider ) {
      $provider->apply_ad_controls_for_user( $user_id );
    }
  }
}

Memberful_Wp_Integration_Ad_Provider_Manager::instance();
