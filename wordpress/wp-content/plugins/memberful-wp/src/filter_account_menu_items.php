<?php
function filter_account_links( $items ) {
  foreach ( $items as $key => $item ) {
    if ( is_user_logged_in() ) {
      if ( $item->url == memberful_sign_in_url()) {
        unset( $items[$key] );
      }
    } else {
      if ( in_array($item->url, [ memberful_sign_out_url(), memberful_account_url() ])) {
        unset( $items[$key] );
      }
    }
  }

  return $items;
}

if ( get_option( 'memberful_filter_account_menu_items' ) && !is_admin() ) {
  add_filter( 'wp_get_nav_menu_items', 'filter_account_links' );
}
