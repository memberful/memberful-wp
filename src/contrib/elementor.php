<?php

add_filter('elementor/frontend/builder_content_data', function($data, $post_id) {
  if (get_queried_object_id() === $post_id) {
    add_action('elementor/frontend/the_content', 'memberful_wp_protect_content');
  }
  return $data;
}, 10, 2);
