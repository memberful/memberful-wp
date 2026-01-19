<?php

add_action( 'pre_get_posts', 'memberful_wp_protect_search' );

function memberful_wp_protect_search( $query ) {
  // Do not protect admin pages.
  if ( is_admin() ) {
    return;
  }

  // Protect only search queries.
  if ( !$query->is_search() ) {
    return;
  }

  // Allow admins to see all posts.
  //
  // Call `current_user_can` only after confirming it's a search query,
  // as WordPress should be fully loaded by then.
  if ( current_user_can( 'publish_posts' ) ) {
    return;
  }

  // Check if protected content should be included in search
  $show_protected_content_in_search = (bool) get_option( 'memberful_show_protected_content_in_search', false );

  /**
   * Filter to determine if protected content should be included in search.
   *
   * @since 1.77.0
   *
   * @param bool $show_protected_content_in_search Whether to show protected content in search.
   * @param WP_Query $query The query object.
   * @return bool Whether to show protected content in search.
   */
  $show_protected_content_in_search = (bool) apply_filters( 'memberful_show_protected_content_in_search', $show_protected_content_in_search, $query );

  if ( $show_protected_content_in_search ) {
    return;
  }

  $disallowed_post_ids = memberful_wp_user_disallowed_post_ids( get_current_user_id() );

  // Exclude posts the user is not allowed to see.
  if ( ! empty( $disallowed_post_ids ) ) {
    $excluded_post_ids = $query->get( 'post__not_in', array() );

    $query->set( 'post__not_in', array_merge( $excluded_post_ids, $disallowed_post_ids ) );
  }
}
