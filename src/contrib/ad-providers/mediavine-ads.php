<?php
/**
 * MediaVine Ads integration.
 *
 * @since 1.78.0
 * @package memberful-wp
 */

/**
 * MediaVine Ads provider class.
 *
 * Disables ads by preventing the MediaVine script wrapper from being enqueued.
 *
 * @see Memberful_Wp_Integration_Ad_Provider_Base
 * @see \Mediavine\MCP\MV_Control_Panel::enqueue_scripts()
 * @package memberful-wp
 * @since 1.78.0
 */
class Memberful_Wp_Integration_Ad_Provider_Mediavine extends Memberful_Wp_Integration_Ad_Provider_Base {

  /**
   * Constructor.
   */
  public function __construct() {
    $this->name       = 'MediaVine Ads';
    $this->identifier = 'mediavine-ads';
    $this->init_hooks();
  }

  /**
   * {@inheritdoc}
   */
  public function is_installed() {
    if ( ! function_exists( 'is_plugin_active' ) ) {
      require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    return is_plugin_active( 'mediavine-control-panel/mediavine-control-panel.php' );
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
   * Disable ads by preventing the MediaVine script wrapper from loading.
   *
   * @param int $user_id The ID of the user to disable ads for.
   */
  public function disable_ads_for_user( $user_id ) {
    add_action( 'wp_enqueue_scripts', array( $this, 'disable_mediavine_ads' ), 99 );
  }

  /**
   * Disable MediaVine ads.
   *
   * De-registers and de-queues the MediaVine script wrapper.
   */
  public function disable_mediavine_ads() {
    wp_deregister_script( 'mv-script-wrapper' );
    wp_dequeue_script( 'mv-script-wrapper' );
  }
}
