<?php

define( 'MEMBERFUL_WP_SINGLE_CUSTOM_FIELD_META_KEY', 'memberful_custom_field' );

/**
 * On multisite the usermeta table is shared by every site in the network,
 * but each site connects to its own Memberful account. Scope per-account
 * meta keys by blog id (mirroring core's per-site capabilities pattern)
 * so sites don't overwrite each other's member data.
 *
 * @param string $meta_key A plain key such as `memberful_product`
 * @return string The key to use in the usermeta table
 */
function memberful_wp_user_meta_key( $meta_key ) {
  if ( ! is_multisite() )
    return $meta_key;

  return 'memberful_'.get_current_blog_id().'_'.substr( $meta_key, strlen( 'memberful_' ) );
}

function memberful_wp_get_user_meta( $user_id, $meta_key, $single = FALSE ) {
  return get_user_meta( $user_id, memberful_wp_user_meta_key( $meta_key ), $single );
}

function memberful_wp_update_user_meta( $user_id, $meta_key, $value ) {
  return update_user_meta( $user_id, memberful_wp_user_meta_key( $meta_key ), $value );
}

function memberful_wp_delete_user_meta( $user_id, $meta_key ) {
  return delete_user_meta( $user_id, memberful_wp_user_meta_key( $meta_key ) );
}

/**
 * Every user meta key the plugin stores member data under.
 *
 * @return array
 */
function memberful_wp_member_user_meta_keys() {
  return array(
    'memberful_product',
    'memberful_subscription',
    'memberful_purchased_subscription',
    'memberful_feed',
    MEMBERFUL_WP_SINGLE_CUSTOM_FIELD_META_KEY,
    'memberful_private_user_feed_token',
    'memberful_expiry_banner_dismissed',
    'memberful_full_name',
  );
}

/**
 * Deletes all of the current site's member data for the user.
 *
 * Needed on multisite when a member is deleted: `wp_delete_user()` only
 * removes the user from the current site, leaving their usermeta in the
 * shared table, and the ACL grants access based on that meta.
 */
function memberful_wp_delete_member_user_meta( $user_id ) {
  foreach ( memberful_wp_member_user_meta_keys() as $meta_key ) {
    memberful_wp_delete_user_meta( $user_id, $meta_key );
  }
}

function memberful_custom_field( WP_User $user ) {
  return memberful_wp_get_user_meta( $user->ID, MEMBERFUL_WP_SINGLE_CUSTOM_FIELD_META_KEY, true );
}

/**
 * The member's name on the current site's Memberful account. The WordPress
 * profile name fields are shared network-wide on multisite and hold whichever
 * site synced most recently, so prefer the per-site name captured during sync.
 */
function memberful_wp_member_name( WP_User $user ) {
  $name = memberful_wp_get_user_meta( $user->ID, 'memberful_full_name', true );

  if ( ! empty( $name ) )
    return $name;

  return trim( $user->user_firstname . ' ' . $user->user_lastname );
}

function memberful_current_user_custom_field() {
  return memberful_custom_field( wp_get_current_user() );
}
