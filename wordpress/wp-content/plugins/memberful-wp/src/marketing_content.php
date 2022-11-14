<?php

define( 'MEMBERFUL_MARKETING_META_KEY', 'memberful_marketing_content' );
define( 'MEMBERFUL_LEGACY_DEFAULT_MARKETING_CONTENT', 'memberful_default_marketing_content' );

function memberful_migrate_from_legacy_default(){
  $legacy=get_option(MEMBERFUL_LEGACY_DEFAULT_MARKETING_CONTENT);

  if( empty($legacy) ){
    return;
  }
  //migrate to new settings
  update_option('memberful_global_marketing_content', $legacy);
  update_option('memberful_use_global_marketing', TRUE);

  //delete legacy option
  delete_option(MEMBERFUL_LEGACY_DEFAULT_MARKETING_CONTENT);
}

add_action('admin_init', 'memberful_migrate_from_legacy_default');

// Get marketing content for the frontend
function memberful_marketing_content( $post_id ) {
  $user_id = is_user_logged_in() ? get_current_user_id() : 0;
  $restricted_posts = memberful_wp_user_disallowed_post_ids( $user_id );

  if ( isset( $restricted_posts[$post_id] )) {
    $marketing_content = memberful_post_marketing_content( $post_id );
  } else {
    $term = memberful_first_term_restricting_post( $user_id, $post_id );
    $marketing_content = memberful_term_marketing_content( $term );
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

function memberful_wp_marketing_content_explanation() {
  return apply_filters('memberful_marketing_content_explanation', '');
}
