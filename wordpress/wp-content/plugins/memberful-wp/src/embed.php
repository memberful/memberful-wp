<?php

add_action('wp_head', 'memberful_wp_render_embed' );

function memberful_wp_render_embed() {
  if ( ! get_option( 'memberful_embed_enabled', FALSE ) || ! memberful_wp_is_connected_to_site() )
    return;

  $script_src       = memberful_wp_embed_script_src();
  $intercepted_urls = array(
    memberful_sign_in_url( 'http' ),
    memberful_sign_in_url( 'https' ),
    memberful_obsolete_sign_in_url( 'http' ),
    memberful_obsolete_sign_in_url( 'https' ),
  );

  memberful_wp_render(
    'embed.js',
    array(
      'script_src'          => memberful_wp_embed_script_src(),
      'memberful_site_url'  => get_option( 'memberful_site' ),
      'intercepted_urls'    => apply_filters( 'memberful_wp_overlay_intercept_urls', $intercepted_urls ),
    )
  );
}

function memberful_wp_embed_script_src() {
  return MEMBERFUL_EMBED_HOST.'/assets/embedded.js';
}
