<?php

add_action( 'bbp_template_redirect', 'memberful_wp_regulate_access_to_bbpress', 1 );

function memberful_wp_regulate_access_to_bbpress() {
  if ( ! is_bbpress() )
    return;

  if ( ! memberful_wp_bbpress_protect_forums() )
    return;

  if ( current_user_can( 'moderate' ) )
    return;

  if( is_user_logged_in() ) {
    if ( memberful_wp_bbpress_restricted_to_registered_users() ) {
      return;
    }

    if ( memberful_wp_bbpress_restricted_to_subscribed_users() && !empty( memberful_wp_user_plans_subscribed_to( get_current_user_id() ) ) ) {
      return;
    }
  }

  $has_required_plan     = memberful_wp_user_has_subscription_to_plans( get_current_user_id(), memberful_wp_bbpress_required_subscription_plans() );
  $has_required_download = memberful_wp_user_has_downloads( get_current_user_id(), memberful_wp_bbpress_required_downloads());

  if ( $has_required_plan || $has_required_download ) {
    return;
  }

  wp_safe_redirect( memberful_wp_bbpress_unauthorized_user_landing_page() );
  exit();
}

function memberful_wp_bbpress_unauthorized_user_landing_page() {
  $use_homepage = memberful_wp_bbpress_send_unauthorized_users_to_homepage();
  $custom_url   = memberful_wp_bbpress_send_unauthorized_users_to_url();

  return ($use_homepage || empty( $custom_url )) ? home_url() : $custom_url;
}


function memberful_wp_bbpress_update_restricted_to_registered_user( $new_value ) {
  return update_option( 'memberful_bbpress_restricted_registered_users', ( $new_value == true ? 1 : 0 ) );
}

function memberful_wp_bbpress_restricted_to_registered_users() {
  return get_option( 'memberful_bbpress_restricted_registered_users', FALSE );
}

function memberful_wp_bbpress_update_restricted_to_subscribed_users( $new_value ) {
  return update_option( 'memberful_bbpress_restricted_subscribed_users', ( $new_value == true ? 1 : 0 ) );
}

function memberful_wp_bbpress_restricted_to_subscribed_users() {
  return get_option( 'memberful_bbpress_restricted_subscribed_users', FALSE );
}

function memberful_wp_bbpress_update_protect_forums( $new_value ) {
  return update_option( 'memberful_bbpress_protect_forums', !! $new_value );
}

function memberful_wp_bbpress_protect_forums() {
  return get_option( 'memberful_bbpress_protect_forums', FALSE );
}

function memberful_wp_bbpress_required_subscription_plans() {
  return get_option( 'memberful_bbpress_required_subscription_plans', array() );
}

function memberful_wp_bbpress_required_downloads() {
  return get_option( 'memberful_bbpress_required_downloads', array() );
}

function memberful_wp_bbpress_update_required_downloads( array $new_downloads ) {
  return update_option( 'memberful_bbpress_required_downloads', $new_downloads );
}

function memberful_wp_bbpress_update_required_subscription_plans( array $plans ) {
  return update_option( 'memberful_bbpress_required_subscription_plans', $plans );
}

function memberful_wp_bbpress_send_unauthorized_users_to_homepage() {
  return get_option( 'memberful_bbpress_send_unauthorized_users_to_homepage', TRUE );
}

function memberful_wp_bbpress_update_send_unauthorized_users_to_homepage( $new_val ) {
  return update_option( 'memberful_bbpress_send_unauthorized_users_to_homepage', ( $new_val == true ? 1 : 0 ) );
}

function memberful_wp_bbpress_update_send_unauthorized_users_to_url( $new_val ) {
  return update_option( 'memberful_bbpress_send_unauthorized_users_to_url', $new_val );
}

function memberful_wp_bbpress_send_unauthorized_users_to_url() {
  return get_option( 'memberful_bbpress_send_unauthorized_users_to_url', '' );
}
