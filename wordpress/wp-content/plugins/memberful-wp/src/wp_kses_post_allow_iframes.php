<?php

add_filter( 'wp_kses_allowed_html', 'wp_kses_post_allow_iframes', 10, 2 );

function wp_kses_post_allow_iframes( $allowedposttags, $context ) {
  if ( $context === 'post' && current_user_can( 'publish_posts' ) ) {
    $allowedposttags['iframe'] = array(
      'allow' => true,
      'allowfullscreen' => true,
      'frameborder' => true,
      'height' => true,
      'src' => true,
      'title' => true,
      'width' => true
    );
  }

  return $allowedposttags;
}
