<?php
/**
 * Ad providers integration.
 *
 * @package memberful-wp
 */

require_once MEMBERFUL_DIR . '/src/contrib/ad-providers/ad-provider-manager.php';

/**
 * Ad providers integration.
 *
 * Registers ad settings and controls for managing ad provider settings.
 */
function memberful_wp_ad_providers_init() {
  // Auto-register ad providers on init.
  Memberful_Wp_Integration_Ad_Provider_Manager::instance()->auto_register_providers();
}

add_action( 'init', 'memberful_wp_ad_providers_init' );
