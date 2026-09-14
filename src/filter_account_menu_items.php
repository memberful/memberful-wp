<?php
function filter_account_links( $items ) {
  foreach ( $items as $key => $item ) {
    if ( is_user_logged_in() ) {
      if ( $item->url == memberful_sign_in_url()) {
        unset( $items[$key] );
      }
    } else {
      $urls = array( memberful_sign_out_url(), memberful_account_url() );
      $custom_domain = get_option( 'memberful_custom_domain' );

      if ( $custom_domain ) {
        $site = get_option( 'memberful_site' );

        foreach( $urls as $url ) {
          array_push( $urls, str_replace( 'https://'.$custom_domain, $site, $url ) );
        }
      }

      if ( in_array($item->url, $urls) ) {
        unset( $items[$key] );
      }
    }
  }

  return $items;
}

if ( get_option( 'memberful_filter_account_menu_items' ) && !is_admin() ) {
  add_filter( 'wp_get_nav_menu_items', 'filter_account_links' );
}
