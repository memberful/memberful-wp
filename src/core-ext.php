<?php

/**
 * Adaptions to core wordpress code that don't fit in other areas.
 *
 * Also includes other misc code.
 */

add_filter( 'allowed_redirect_hosts', 'memberful_wp_allowed_hosts' );

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

function memberful_wp_kses_post( $string ) {
  $allowed_html = array_merge(
    wp_kses_allowed_html( 'post' ),
    array(
      'iframe' => array(
        'allow' => true,
        'allowfullscreen' => true,
        'frameborder' => true,
        'height' => true,
        'loading' => true,
        'src' => true,
        'width' => true
      )
    )
  );

  return wp_kses( $string, $allowed_html );
}

function memberful_wp_render( $template, array $vars = array() ) {
  extract( $vars );

  include MEMBERFUL_DIR.'/views/'.$template.'.php';
}
