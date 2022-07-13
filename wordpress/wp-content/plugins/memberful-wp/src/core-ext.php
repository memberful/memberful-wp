<?php

/**
 * Adaptions to core wordpress code that don't fit in other areas.
 *
 * Also includes other misc code.
 */

add_filter( 'allowed_redirect_hosts', 'memberful_wp_allowed_hosts' );

/**
 * Returns a member-specific RSS URL for a provided podcast ID
 * This is specific to the Podcast Player plugin
 */
add_filter( 'podcast_player_display_args', function( $args ) {
    
    // Website owner will enter podcast ID in the feed URL field.
    $url_id = isset( $args[ 'url' ] ) ? $args[ 'url' ] : false;
    if ( ! $url_id ) {
        return $args;
    }

    // Get private URL from the feed ID.
    $actual_url = memberful_wp_feed_url( $url_id );
    if ( ! $actual_url ) {
        return $args;
    }

    // Replace ID with actual private URL in display args.
    $args[ 'url' ] = esc_url_raw( $actual_url );

    // Let's not allow sharing and download the audio.
    $args[ 'hide-download' ] = 'true';
    $args[ 'hide-social' ]   = 'true';

    return $args;
} );

function memberful_wp_valid_nonce( $action ) {
  return isset( $_POST['memberful_nonce'] ) && wp_verify_nonce( $_POST['memberful_nonce'], $action );
}

function memberful_wp_nonce_field( $action ) {
  return wp_nonce_field( $action, 'memberful_nonce' );
}

/**
 * Adds the Memberful domain to the list of allowed redirect hosts
 * @param array $content A set of websites that can be redirected to
 * @return array The $content plus Memberful domain
 */
function memberful_wp_allowed_hosts( $hosts ) {
  $site = get_option( 'memberful_site' );
  $custom_domain = get_option( 'memberful_custom_domain' );

  if ( $site ) {
    $components = parse_url( $site );

    if ( $components ) {
      array_push($hosts, $components['host']);
    }
  }

  if ( $custom_domain ) {
    array_push($hosts, $custom_domain);
  }

  return $hosts;
}

function memberful_wp_render( $template, array $vars = array() ) {
  extract( $vars );

  include MEMBERFUL_DIR.'/views/'.$template.'.php';
}
