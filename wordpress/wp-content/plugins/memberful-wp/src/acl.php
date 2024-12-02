<?php
require_once MEMBERFUL_DIR . '/src/acl/helpers.php';
require_once MEMBERFUL_DIR . '/src/acl/post_options.php';
require_once MEMBERFUL_DIR . '/src/acl/term_options.php';

/**
 * Determines the set of post IDs that the current user cannot access
 *
 * If a page/post requires products a,b then the user will be granted access
 * to the content if they have bought either product a or b
 *
 * TODO: This is calculated on every page load, maybe use a cache?
 *
 * @return array Map of post ID => post ID
 */
function memberful_wp_user_disallowed_post_ids( $user_id ) {
  $acl = get_option( 'memberful_acl', array() );
  $user_signed_in = $user_id !== 0;
  $user_subs = memberful_wp_user_plans_subscribed_to( $user_id );

  $posts_user_is_not_allowed_to_access = _memberful_wp_get_protected_ids_from_acl( $acl, $user_id );

  if ( empty( $user_subs ) ) {
    $posts_user_is_not_allowed_to_access = array_merge(
      $posts_user_is_not_allowed_to_access,
      memberful_wp_get_all_posts_available_to_anybody_subscribed_to_a_plan()
    );
  }

  if ( !$user_signed_in ) {
    $posts_user_is_not_allowed_to_access = array_merge(
      $posts_user_is_not_allowed_to_access,
      memberful_wp_get_all_posts_available_to_any_registered_user()
    );
  }

  return ( empty( $posts_user_is_not_allowed_to_access ) ) ? array() : array_combine( $posts_user_is_not_allowed_to_access, $posts_user_is_not_allowed_to_access );
}

function memberful_wp_user_disallowed_term_ids( $user_id ) {
  $acl = get_option( 'memberful_term_acl', array() );
  $user_signed_in = $user_id !== 0;
  $user_subs = memberful_wp_user_plans_subscribed_to( $user_id );

  $terms_user_is_not_allowed_to_access = _memberful_wp_get_protected_ids_from_acl( $acl, $user_id );

  if ( empty( $user_subs ) ) {
    $terms_user_is_not_allowed_to_access = array_merge(
      $terms_user_is_not_allowed_to_access,
      memberful_wp_get_all_terms_available_to_anybody_subscribed_to_a_plan()
    );
  }

  if ( !$user_signed_in ) {
    $terms_user_is_not_allowed_to_access = array_merge(
      $terms_user_is_not_allowed_to_access,
      memberful_wp_get_all_terms_available_to_any_registered_user()
    );
  }

  return ( empty( $terms_user_is_not_allowed_to_access ) ) ? array() : array_combine( $terms_user_is_not_allowed_to_access, $terms_user_is_not_allowed_to_access );
}

function _memberful_wp_get_protected_ids_from_acl( $acl, $user_id ) {
  $user_products = memberful_wp_user_downloads( $user_id );
  $user_subs     = memberful_wp_user_plans_subscribed_to( $user_id );

  $product_acl = isset( $acl['product'] ) ? $acl['product'] : array();
  $subscription_acl = isset( $acl['subscription'] ) ? $acl['subscription'] : array();

  // Work out the set of items the user is and isn't allowed to access
  $user_product_acl      = memberful_wp_generate_user_specific_acl_from_global_acl( $user_products, $product_acl );
  $user_subscription_acl = memberful_wp_generate_user_specific_acl_from_global_acl( $user_subs, $subscription_acl );

  $user_allowed_items    = array_merge( $user_product_acl['allowed'],    $user_subscription_acl['allowed'] );
  $user_restricted_items = array_merge( $user_product_acl['restricted'], $user_subscription_acl['restricted'] );

  return array_diff( $user_restricted_items, $user_allowed_items );
}

/**
 * Given a set of products/subscriptions that the member has, and the corresponding
 * product/subscription acl for the site, work out what posts they can view.
 *
 * @param  array $users_entities An array of ids (either product ids or subscription ids) in form id => id.
 * @param  array $acl            Global acl for the entity type.
 * @return
 */
function memberful_wp_generate_user_specific_acl_from_global_acl( $users_entities, $acl ) {
  if ( empty( $users_entities ) )
    $users_entities = array();

  $allowed_entities    = array_intersect_key( $acl, $users_entities );
  $restricted_entities = array_diff_key( $acl, $users_entities );

  $allowed_ids    = array();
  $restricted_ids = array();

  foreach ( $allowed_entities as $posts ) {
    $allowed_ids = array_merge( $allowed_ids, $posts );
  }

  foreach ( $restricted_entities as $posts ) {
    $restricted_ids = array_merge( $restricted_ids, $posts );
  }

  // array_merge doesn't preserve keys
  $allowed    = array_unique( $allowed_ids );
  $restricted = array_unique( $restricted_ids );

  return array( 'allowed' => $allowed, 'restricted' => $restricted );
}

/**
 * Gets the array of products the member with $member_id owns
 *
 * @return array member's products
 */
function memberful_wp_user_downloads( $user_id ) {
  return memberful_wp_get_user_meta_for_acl( $user_id, 'memberful_product' );
}

function memberful_wp_user_feeds($user_id) {
  return memberful_wp_get_user_meta_for_acl($user_id, 'memberful_feed');
}

/**
 * Gets the plans that the member with $member_id is currently subscribed to
 * If the member had a subscription to a plan, but it has expired then it
 * is not included in this list.
 *
 * @return array member's subscriptions
 */
function memberful_wp_user_plans_subscribed_to( $user_id ) {
  return memberful_wp_get_user_meta_for_acl( $user_id, 'memberful_subscription' );
}

/**
 * `get_user_meta` may return `false` or `""` if the given $user_id
 * doesn't exist or is invalid. Because the expected ACL interface is an array,
 * we return an empty one.
 */
function memberful_wp_get_user_meta_for_acl($user_id, $meta_key, $single = TRUE) {
  $meta = get_user_meta($user_id, $meta_key, $single);

  if ($meta == false)
    $meta = array();

  return $meta;
}

/**
 * Gets the download the current member has
 *
 * @return array current member's downloads
 */
function memberful_wp_current_user_downloads() {
  $current_user = wp_get_current_user();
  return memberful_wp_user_downloads( $current_user->ID );
}

/**
 * Check that the specified user is subscribed to at least one of the specified plans
 *
 * @param int   $user_id The id of the wordpress user
 * @param array $subscriptions Ids of the subscriptions to restrict access to
 * @return boolean
 */
function memberful_wp_user_has_subscription_to_plans( $user_id, array $required_plans ) {
  $plans_user_is_subscribed_to = memberful_wp_user_plans_subscribed_to( $user_id );

  foreach ( $required_plans as $plan ) {
    if ( isset( $plans_user_is_subscribed_to[ $plan ] ) ) {
      return TRUE;
    }
  }

  return FALSE;
}

/**
 * Check that the specified user has at least one of a set of products
 *
 * @param int   $user_id   The id of the wordpress user
 * @param array $downloads Ids of the downloads to check the user has
 * @return boolean
 */
function memberful_wp_user_has_downloads( $user_id, $required_downloads ) {
  $downloads_user_has = memberful_wp_user_downloads( $user_id );

  foreach ( $required_downloads as $download ) {
    if ( isset( $downloads_user_has[ $download ] ) )
      return TRUE;
  }

  return FALSE;
}

function memberful_wp_user_has_feeds($user_id, $feeds) {
  $user_feeds = memberful_wp_user_feeds($user_id);

  foreach ($feeds as $feed) {
    if (isset($user_feeds[$feed]))
      return TRUE;
  }

  return FALSE;
}

/**
 * Extracts ids, and a user ID from the arguments passed to one of the
 * has_memberful_* helpers.
 *
 * @param array $args ALL arguments passed to the original helper
 * @return array      Array of IDs extract from the slugs as first element, user id as second
 */
function memberful_wp_extract_slug_ids_and_user($args) {
  $slugs = $args[0];
  $user  = empty($args[1]) ? NULL : $args[1];

  if ( $user === NULL )
    $user = wp_get_current_user()->ID;

  return array( memberful_wp_slugs_to_ids( $slugs ), $user );
}

/**
 * Checks that the user has permission to access the specified post
 *
 * @param integer $user_id ID of the user
 * @param integer $post_id ID of the post that should have access checked
 */
function memberful_can_user_access_post( $user, $post ) {
  $user_subs = $user ? array_keys( memberful_wp_user_plans_subscribed_to( $user )) : array();
  $terms_for_post = memberful_wp_get_term_ids_for_post( $post );

  // Grant access if registered user and post or one of its terms allows any registered user
  if ( memberful_wp_post_viewable_by_any_registered_user( $post, $terms_for_post )) {
    return $user ? true : false;
  }

  // Grant access if user has a subscription and post or one of its terms allows access with any subscription
  if ( memberful_wp_post_viewable_by_any_subscriber( $post, $terms_for_post )) {
    return !empty( $user_subs );
  }

  // Get the set of restrictions for this post
  $post_acl = get_post_meta( $post, 'memberful_acl', TRUE );
  $plans_for_post = isset( $post_acl['subscription'] ) ? $post_acl['subscription'] : array();
  $products_for_post = isset( $post_acl['product'] ) ? $post_acl['product'] : array();

  // Get the term-level restrictions for all posts
  $terms_acl = get_option( 'memberful_term_acl', array() );
  $global_subscription_term_acl = isset( $terms_acl['subscription'] ) ? $terms_acl['subscription'] : array();
  $global_product_term_acl = isset( $terms_acl['product'] ) ? $terms_acl['product'] : array();

  // Find plans required to view the terms attached to this post
  foreach ( $global_subscription_term_acl as $plan => $terms ) {
    if ( array_intersect( $terms, $terms_for_post )) {
      $plans_for_post[] = $plan;
    }
  }

  // Find products required to view the terms attached to this post
  foreach ( $global_product_term_acl as $product=> $terms ) {
    if ( array_intersect( $terms, $terms_for_post )) {
      $products_for_post[] = $product;
    }
  }

  // Find any of the required plans that the current user has
  $plan_intersect = array_intersect( $plans_for_post, $user_subs );

  // Find any of the required products that the current user has
  $user_products = $user ? array_keys( memberful_wp_user_products( $user )) : array();
  $product_intersect = array_intersect( $products_for_post, $user_products );

  if (( empty( $plans_for_post ) ) && ( empty( $products_for_post ))) {
    // Grant access if no restrictions
    return true;
  } elseif ( ! empty( $plan_intersect ) || ! empty( $product_intersect )) {
    // Grant access if any plans or products required and the user has at least one
    return true;
  } else {
    // The post requires at least one plan or product to access and the user has none of those specified
    return false;
  }
}

function memberful_wp_post_viewable_by_any_registered_user( $post, $terms_for_post ) {
  $posts_for_any_registered_users = memberful_wp_get_all_posts_available_to_any_registered_user();
  $terms_for_any_registered_users = memberful_wp_get_all_terms_available_to_any_registered_user();

  return ( in_array( $post, $posts_for_any_registered_users ) || array_intersect( $terms_for_post, $terms_for_any_registered_users ));
}

function memberful_wp_post_viewable_by_any_subscriber( $post, $terms_for_post ) {
  $posts_for_anybody_subscribed_to_a_plan = memberful_wp_get_all_posts_available_to_anybody_subscribed_to_a_plan();
  $terms_for_anybody_subscribed_to_a_plan = memberful_wp_get_all_terms_available_to_anybody_subscribed_to_a_plan();

  return ( in_array( $post, $posts_for_anybody_subscribed_to_a_plan ) || array_intersect( $terms_for_post, $terms_for_anybody_subscribed_to_a_plan ));
}

function memberful_first_term_restricting_post( $user, $post ) {
  $restricted_terms = array_values( memberful_wp_user_disallowed_term_ids( $user ));
  $post_terms = memberful_wp_get_term_ids_for_post( $post );

  if ( !empty( $restricted_terms ) && !empty( $post_terms )) {
    $terms_restricted_to_specific_plans = array_intersect( $restricted_terms, $post_terms );

    if ( !empty( $terms_restricted_to_specific_plans )) {
      return reset( $terms_restricted_to_specific_plans );
    }
  }
}

function memberful_wp_get_term_ids_for_post( $post ) {
  $taxonomies = memberful_supported_taxonomies();
  $terms = wp_get_post_terms( $post, $taxonomies );

  if ( !empty( $terms )) {
    $terms = wp_list_pluck( $terms, "term_id" );
  }

  return $terms;
}

function memberful_supported_taxonomies() {
  static $taxonomies = NULL;

  if ( $taxonomies !== NULL ) {
    return $taxonomies;
  }

  $taxonomies = get_taxonomies( array( "public" => true, "show_in_menu" => true ) );

  return $taxonomies;
}
