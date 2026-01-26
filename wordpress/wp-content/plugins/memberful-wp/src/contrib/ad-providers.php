<?php
/**
 * Ad providers integration.
 *
 * @package memberful-wp
 */

require_once MEMBERFUL_DIR . '/src/contrib/ad-providers/ad-provider-manager.php';
require_once MEMBERFUL_DIR . '/src/contrib/ad-providers/raptive-ads.php';

/**
 * Ad providers integration.
 *
 * Registers ad settings and controls for managing ad provider settings.
 */
function memberful_wp_ad_providers_init() {
  Memberful_Wp_Integration_Ad_Provider_Manager::instance()->init();
}
add_action( 'init', 'memberful_wp_ad_providers_init' );

/**
 * Register officially supported ad providers.
 */
function memberful_wp_ad_providers_register_providers() {
  $ad_provider_manger = Memberful_Wp_Integration_Ad_Provider_Manager::instance();

  // Raptive Ads (AdThrive Ads).
  $ad_provider_manger->register_provider( new Memberful_Wp_Integration_Ad_Provider_Raptive() );
}
add_action( 'memberful_ad_provider_register_providers', 'memberful_wp_ad_providers_register_providers' );
