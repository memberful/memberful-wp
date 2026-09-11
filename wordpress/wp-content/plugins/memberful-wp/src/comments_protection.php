<?php

add_action( 'template_redirect', 'memberful_comments_protection_template_redirect' );


/**
 * This function will Hide comments on protected posts - #124
 */
function memberful_comments_protection_template_redirect() {

  //Check if we should apply the filter
  if( !is_singular() || memberful_user_can_access_comments()) return;

  // Replace the comments template with the one used by us.
  add_filter( 'comments_template', 'memberful_comments_protection_comments_template', 20 );

  wp_deregister_script( 'comment-reply' );
  remove_action( 'wp_head', 'feed_links_extra', 3 );
}

function memberful_comments_protection_comments_template() {
  return MEMBERFUL_DIR . '/views/comments-template.php';
}

/**
 * Function checks to see if the user should have access to the comments
 * @global type $post
 * @return boolean
 */
function memberful_user_can_access_comments(){
      // The user most has admin - publisher rights, so we'll not restrict comments access.
  if (current_user_can( 'publish_posts' )) return true;

  global $post;

  $post_id = isset( $post->ID ) ? (int) $post->ID : get_queried_object_id();

  if ( ! $post_id )
    return true;

  // User has access to this post, we won't do anything.
  return memberful_can_user_access_post( wp_get_current_user()->ID, $post_id );
}


add_action( 'do_feed_rss2', 'memberful_single_feed_comments_protection', 9, 1 );
add_action( 'do_feed_atom', 'memberful_single_feed_comments_protection', 9, 1 );

/**
*Function checks to see if this is a comments feed that should be removed
*@return null
*/
function memberful_single_feed_comments_protection($for_comments){
    if ( ! $for_comments || ! is_singular() || ! memberful_post_is_protected() )
      return;

    memberful_remove_feed();
}

/**
*Function to see if memberful protects the current post id'd by $post_id
*
*@return bool
*/
function memberful_post_is_protected($post_id=null){

  if(!isset($post_id))
    $post_id=get_the_ID();

  if ( empty( $post_id ) )
    return false;

  return ! memberful_can_user_access_post( 0, $post_id );
}

/**
*Function to remove rss2 feed and atom feed
*@return null
*/
function memberful_remove_feed(){
  remove_action( 'do_feed_rss2', 'do_feed_rss2', 10, 1 );
  remove_action( 'do_feed_atom', 'do_feed_atom', 10, 1 );
}

add_filter('comment_feed_where', 'memberful_comment_feed_cwhere_filter', 10, 2);

/**
* Filter function to directly edit WP_Query's WHERE statement
* when accessing the comments feed.
*/

function memberful_comment_feed_cwhere_filter($cwhere, $query){
  if ( ! $query->is_feed() || $query->is_singular() )
    return $cwhere;

  $restricted = memberful_wp_user_disallowed_post_ids( 0 );

  if ( empty( $restricted ) )
    return $cwhere;

  global $wpdb;
  $ids     = implode( ',', array_map( 'absint', $restricted ) );
  $cwhere .= " AND {$wpdb->posts}.ID NOT IN ($ids)";

  return $cwhere;
}
?>
