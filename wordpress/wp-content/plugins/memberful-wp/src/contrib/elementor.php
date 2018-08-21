<?php
use Elementor\Plugin;

add_action( 'wp', 'memberful_disable_elementor_content_filter' );

function memberful_disable_elementor_content_filter() {
  global $post;

  if ( ! memberful_can_user_access_post( wp_get_current_user()->ID, $post->ID ) ) {
    $elementor = Plugin::instance();
    $elementor->frontend->remove_content_filter();
  }
}
