<?php

add_action('wp_head', 'memberful_wp_render_embed' );

function memberful_wp_render_embed() {
  if ( ! get_option( 'memberful_embed_enabled', FALSE ) || ! memberful_wp_is_connected_to_site() )
    return;

  memberful_wp_render(
    'embed.js',
    array(
      'script_src' => memberful_wp_embed_script_src(),
      'memberful_site_url' => memberful_url()
    )
  );
}

function memberful_wp_embed_script_src() {
  return MEMBERFUL_EMBED_HOST.'/assets/embedded.js';
}
