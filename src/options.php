<?php
require MEMBERFUL_DIR.'/src/user/map_stats.php';

function memberful_wp_all_options() {
  return array(
    'memberful_client_id' => NULL,
    'memberful_client_secret' => NULL,
    'memberful_site' => NULL,
    'memberful_api_key' => NULL,
    'memberful_webhook_secret' => NULL,
    'memberful_products' => array(),
    'memberful_subscriptions' => array(),
    'memberful_acl' => array(),
    'memberful_embed_enabled' => FALSE,
    'memberful_error_log' => array(),
    'memberful_role_active_customer' => 'subscriber',
    'memberful_role_inactive_customer' => 'subscriber',
    'memberful_posts_available_to_any_registered_user' => array(),
    MEMBERFUL_OPTION_DEFAULT_MARKETING_CONTENT => NULL
  );
}

/**
 * Options that need to be reset when disconnecting from memberful.com
 * @return array
 */
function memberful_wp_connection_options() {
  return array(
    'memberful_client_id',
    'memberful_client_secret',
    'memberful_api_key',
    'memberful_webhook_secret'
  );
}

function memberful_wp_is_connected_to_site() {
  return !! get_option( 'memberful_client_id', FALSE );
}

function memberful_wp_register_options() {
  foreach ( memberful_wp_all_options() as $option => $default ) {
    add_option( $option, $default );
  }
}

function memberful_wp_option_values() {
  $config = array();

  foreach ( memberful_wp_all_options() as $option => $default ) {
    $value = get_option( $option );

    $config[$option] = is_string( $value ) ? stripslashes( $value ) : $value;
  }

  return $config;
}

function memberful_wp_site_name() {
  $blog_name = wp_specialchars_decode( trim( get_bloginfo( 'name', 'Display' ) ), ENT_QUOTES );

  return empty( $blog_name ) ? 'WordPress Blog' : $blog_name;
}

function memberful_wp_send_site_options_to_memberful() {
  $options = array('site' => array('name' => memberful_wp_site_name(), 'main_website_url' => home_url()));

  memberful_wp_put_data_to_api_as_json( 
    memberful_url( 'admin/settings/integrate/website/settings' ),
    $options
  );
}
