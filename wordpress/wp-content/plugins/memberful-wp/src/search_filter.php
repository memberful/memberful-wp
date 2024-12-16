<?php

add_action( 'pre_get_posts', 'memberful_wp_protect_search' );

function memberful_wp_protect_search( $query ) {
  // Do not filter search results for admins
  if ( current_user_can( 'publish_posts' ) ) {
    return $query;
  }

  if ( !is_admin() && $query->is_search() ) {
    $disallowed_post_ids = memberful_wp_user_disallowed_post_ids( get_current_user_id() );

    // Exclude posts that the user is not allowed to see.
    if ( ! empty( $disallowed_post_ids ) ) {
      $excluded_post_ids = $query->get( 'post__not_in', array() );

      $query->set( 'post__not_in', array_merge( $excluded_post_ids, $disallowed_post_ids ) );
    }
  }
}
