<?php

add_action('wp_head', 'memberful_wp_render_embed' );

function memberful_wp_render_embed() {
    if ( get_option( 'memberful_embed_enabled', FALSE ) )
      memberful_wp_render('embed.js');
}
