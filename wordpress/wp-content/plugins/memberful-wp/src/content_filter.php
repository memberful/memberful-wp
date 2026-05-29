<?php

add_action( 'the_content', 'memberful_wp_protect_content', 100 );

function memberful_wp_protect_content( $content ) {
  global $post;

  if ( !isset( $post ) ) {
    # Return the content since we're not in the loop if `$post` is `NULL`
    # Temporary fix for Elasticpress' syncing issue
    return $content;
  }

  if(doing_filter('memberful_wp_protect_content')){
    return $content;
  }

  // Do not filter content for admins
  if ( current_user_can( 'publish_posts' ) ) {
    return $content;
  }

  if ( ! memberful_can_user_access_post( wp_get_current_user()->ID, $post->ID ) ) {
    // Disable Beaver Builder
    remove_action( "the_content", "FLBuilder::render_content" );

    // Remove Elementor action hook
    if (get_queried_object_id() === $post->ID) {
      remove_action("elementor/frontend/the_content", "memberful_wp_protect_content");
    }

    // Remove media enclosures from the RSS feed
    add_filter("rss_enclosure", "__return_empty_string");

    $memberful_marketing_content = memberful_marketing_content( $post->ID );
    return apply_filters( 'memberful_wp_protect_content', $memberful_marketing_content );
  }

  return $content;
}

add_filter( 'memberful_wp_protect_content','wptexturize');
add_filter( 'memberful_wp_protect_content','convert_smilies');
add_filter( 'memberful_wp_protect_content','convert_chars');
add_filter( 'memberful_wp_protect_content','wpautop');
add_filter( 'memberful_wp_protect_content','shortcode_unautop');
add_filter( 'memberful_wp_protect_content','prepend_attachment');

add_filter('memberful_wp_protect_content','do_blocks',15);
add_filter( 'memberful_wp_protect_content', 'do_shortcode', 11 );

if ( get_option( 'memberful_use_global_marketing' ) ) {
  include_once 'global_marketing.php';
}
