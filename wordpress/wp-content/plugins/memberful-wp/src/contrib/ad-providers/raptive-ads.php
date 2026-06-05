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
    add_action( 'wp_head', array( $this, 'disable_video_player' ), 99 );
  }

  /**
   * Signal to Raptive's ad script that video players are disabled.
   *
   * The adthrive-disable-video body class suppresses video on desktop, but the
   * mobile sticky playlist player keys off the videoDisabledFromPlugin flag that
   * Raptive's own per-page disable sets. Raptive derives that flag from post meta,
   * not from filter-added body classes, so our body class alone does not trigger
   * it. We replicate the flag here.
   *
   * Printed late on wp_head (priority 99) so it runs after Raptive defines
   * window.adthriveCLS (priority 1) regardless of Raptive's priority, but still
   * before the async ads.min.js executes. Merges rather than overwrites so the
   * existing config is preserved.
   *
   * @see insertion-includes.php in the Raptive Ads (AdThrive) plugin.
   */
  public function disable_video_player() {
    echo "<script>window.adthriveCLS = window.adthriveCLS || {}; window.adthriveCLS.videoDisabledFromPlugin = true;</script>\n";
  }

  /**
   * Disable ads for the Raptive Ads provider.
   *
   * Uses the adthrive-disable-all body class to disable ads, plus
   * adthrive-disable-video to disable video players.
   *
   * @see \AdThrive_Ads\Components\Ads\Main::body_class()
   *
   * @param array $classes The body classes.
   * @return array The body classes.
   */
  public function disable_ads_body_class( $classes ) {
    $classes[] = 'adthrive-disable-all';
    $classes[] = 'adthrive-disable-video';
    return $classes;
  }
}
