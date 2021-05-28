<?php

define( 'MEMBERFUL_MARKETING_META_KEY', 'memberful_marketing_content' );
define( 'MEMBERFUL_OPTION_DEFAULT_MARKETING_CONTENT', 'memberful_default_marketing_content' );

// Get marketing content for the frontend
function memberful_marketing_content( $post_id ) {
  $user_id = is_user_logged_in() ? get_current_user_id() : 0;
  $restricted_posts = memberful_wp_user_disallowed_post_ids( $user_id );

  if ( isset( $restricted_posts[$post_id] )) {
    $marketing_content = memberful_post_marketing_content( $post_id );
  } else {
    $terms = memberful_terms_restricting_post( $user_id, $post_id );
    $marketing_content = memberful_term_marketing_content( reset( $terms ));
  }

  return apply_filters( 'memberful_marketing_content', $marketing_content );
}

function memberful_post_marketing_content( $post_id ) {
  return get_post_meta( $post_id, MEMBERFUL_MARKETING_META_KEY, TRUE );
}

function memberful_term_marketing_content( $term_id ) {
  return get_term_meta( $term_id, MEMBERFUL_MARKETING_META_KEY, TRUE );
}

function memberful_wp_update_post_marketing_content( $post_id, $content ) {
  update_post_meta( $post_id, MEMBERFUL_MARKETING_META_KEY, $content );
}

function memberful_wp_update_term_marketing_content( $term_id, $content ) {
  update_term_meta( $term_id, MEMBERFUL_MARKETING_META_KEY, $content );
}

function memberful_wp_update_default_marketing_content( $content ) {
  update_option( MEMBERFUL_OPTION_DEFAULT_MARKETING_CONTENT, $content );
}

function memberful_wp_default_marketing_content() {
  return stripslashes( get_option( MEMBERFUL_OPTION_DEFAULT_MARKETING_CONTENT, '' ) );
}

function memberful_wp_marketing_content_explanation() {
  return __( "This marketing content will be shown in place of your protected content to anyone who is not allowed to read the post..." );
}
