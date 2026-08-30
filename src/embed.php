<?php

add_action( 'wp_head', 'memberful_wp_render_embed' );

function memberful_wp_render_embed() {
  if ( ! get_option( 'memberful_embed_enabled', FALSE ) || ! memberful_wp_is_connected_to_site() )
    return;

  $custom_domain = get_option( 'memberful_custom_domain' );

  if ( $custom_domain ) {
    $site_option = array( 'https://'.$custom_domain , get_option( 'memberful_site' ) );
  } else {
    $site_option = array( get_option( 'memberful_site' ) );
  }

  memberful_wp_render(
    'embed.js',
    array(
      'script_src' => memberful_wp_embed_script_src(),
      'site_option' => $site_option
    )
  );
}

function memberful_wp_embed_script_src() {
  return MEMBERFUL_EMBED_HOST.'/embed.js';
}
