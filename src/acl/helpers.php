<?php

/**
 * @deprecated 1.6.0
 */
function memberful_wp_current_user_products() {
	return memberful_wp_current_user_downloads();
}

/**
 * @deprecated 1.6.0
 */
function has_memberful_subscription( $slug, $user_id = NULL ) {
	return is_subscribed_to_memberful_plan( $slug, $user_id );
}

/**
 * @deprecated 1.6.0
 */
function has_memberful_product( $slug, $user_id = NULL ) {
	return has_memberful_download( $slug, $user_id );
}

/**
 * @deprecated 1.6.0
 */
function memberful_wp_user_products( $user_id ) {
	return memberful_wp_user_downloads( $user_id );
}

/**
 * @deprecated 1.6.0
 */
function memberful_wp_user_subscriptions( $user_id ) {
	return memberful_wp_user_plans_subscribed_to( $user_id );
}

/**
 * @deprecated 1.6.0
 */
function memberful_wp_user_has_products( $user_id, array $products ) {
	return memberful_wp_user_has_downloads( $user_id, $products );
}

/**
 * @deprecated 1.6.0
 */
function memberful_wp_user_has_subscriptions( $user_id, array $subscriptions ) {
	return memberful_wp_user_has_subscription_to_plans( $user_id, $subscriptions );
}

/**
 * Check that the current member has a subscription to at least least one of the required plans
 *
 * @param string|array $slug    Slug of the plan the user should have. Can pass an array of slugs
 * @param int          $user_id ID of the user who should have the subscription, defaults to current user
 * @return bool
 */
function is_subscribed_to_memberful_plan( $slug, $user_id = NULL ) {
	list( $required_plans , $user_id ) = memberful_wp_extract_slug_ids_and_user( func_get_args() );

	return memberful_wp_user_has_subscription_to_plans( $user_id, $required_plans );
}

/**
 * Check that the current member has at least one of the specified products
 *
 * @param string|array $slug    Slug of the product the user should have. Can pass an array of slugs
 * @param int          $user_id ID of the user who should have the product, defaults to current user
 * @return bool
 */
function has_memberful_download( $slug, $user_id = NULL ) {
	list( $required_downloads, $user_id ) = memberful_wp_extract_slug_ids_and_user( func_get_args() );

	return memberful_wp_user_has_downloads( $user_id, $required_downloads );
}

function memberful_wp_posts_that_are_protected() {
	static $post_ids = NULL;

	if ( $post_ids !== NULL ) {
		return $post_ids;
	}

	$global_acl = get_option( 'memberful_acl' );
	$post_ids   = array();

	// Loops in loops aren't good, but at least we cache this with the static call
	foreach( $global_acl as $entity_type => $acl ) {
		foreach( $acl as $purchasable_id => $posts_this_item_grants_access_to) {
			$post_ids = array_merge( $post_ids, array_values($posts_this_item_grants_access_to) );
		}
	}

	$post_ids = array_unique( $post_ids );

	return empty( $post_ids ) ? array() : $post_ids;
}
