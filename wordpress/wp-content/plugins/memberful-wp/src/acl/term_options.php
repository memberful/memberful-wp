<?php

function memberful_wp_is_term_available_to_any_registered_users( $term_id ) {
  return get_term_meta( $term_id, 'memberful_available_to_any_registered_user', TRUE ) === "1";
}

function memberful_wp_set_term_available_to_any_registered_users( $term_id, $is_viewable_by_any_registered_users ) {
  update_term_meta( $term_id, 'memberful_available_to_any_registered_user', $is_viewable_by_any_registered_users);

  $globally_viewable_by_any_registered_users = memberful_wp_get_all_terms_available_to_any_registered_user();

  if ( $is_viewable_by_any_registered_users ) {
    $globally_viewable_by_any_registered_users[$term_id] = $term_id;
  } else {
    unset($globally_viewable_by_any_registered_users[$term_id]);
  }

  update_option( 'memberful_terms_available_to_any_registered_user', $globally_viewable_by_any_registered_users );
}

function memberful_wp_get_all_terms_available_to_any_registered_user() {
  return get_option( 'memberful_terms_available_to_any_registered_user', array() );
}

function memberful_wp_is_term_available_to_anybody_subscribed_to_a_plan( $term_id ) {
  return get_term_meta( $term_id, 'memberful_available_to_anybody_subscribed_to_a_plan', TRUE ) === "1";
}

function memberful_wp_set_term_available_to_anybody_subscribed_to_a_plan( $term_id, $is_viewable ) {
  update_term_meta( $term_id, 'memberful_available_to_anybody_subscribed_to_a_plan', $is_viewable );

  $terms_available_to_anybody_subscribed_to_a_plan = memberful_wp_get_all_terms_available_to_anybody_subscribed_to_a_plan();

  if ( $is_viewable ) {
    $terms_available_to_anybody_subscribed_to_a_plan[$term_id] = $term_id;
  } else {
    unset($terms_available_to_anybody_subscribed_to_a_plan[$term_id]);
  }

  update_option( 'memberful_terms_available_to_anybody_subscribed_to_a_plan', $terms_available_to_anybody_subscribed_to_a_plan);
}

function memberful_wp_get_all_terms_available_to_anybody_subscribed_to_a_plan() {
  return get_option( 'memberful_terms_available_to_anybody_subscribed_to_a_plan', array() );
}
