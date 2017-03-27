<?php

add_action( 'template_redirect', 'memberful_comments_protection_template_redirect' );

/**
 * This function will Hide comments on protected posts - #124
 */
function memberful_comments_protection_template_redirect() {
  // The user most has admin - publisher rights, so we'll not restrict comments access.
  if ( current_user_can( 'publish_posts' ) )
    return;

  // This isn't a single post page, so we don't do anything.
  if( !is_singular() )
    return;

  global $post;

  // User has access to this post, we won't do anything.
  if ( memberful_can_user_access_post( wp_get_current_user()->ID, $post->ID ) )
    return;

  // Replace the comments template with the one used by us.
  add_filter( 'comments_template', 'memberful_comments_protection_comments_template', 20 );

  wp_deregister_script( 'comment-reply' );
  remove_action( 'wp_head', 'feed_links_extra', 3 );
}

function memberful_comments_protection_comments_template() {
  return MEMBERFUL_DIR . '/views/comments-template.php';
}