<?php

add_action( 'request', 'memberful_audit_request' );
add_action( 'pre_get_posts', 'memberful_filter_posts' );


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
function memberful_user_disallowed_post_ids()
{
	static $ids = NULL;;

	if ( is_admin() )
		return array();

	if ( $ids !== NULL )
		return $ids;

	$acl = get_option( 'memberful_acl', TRUE );

	// The products the user has access to
	$user_products = get_user_meta( wp_get_current_user()->ID, 'memberful_products', TRUE );

	if ( empty( $user_products ) )
		$user_products = array();

	$allowed_products    = array_intersect_key( $acl, $user_products );
	$restricted_products = array_diff_key( $acl, $user_products );

	$allowed_ids    = array();
	$restricted_ids = array();

	foreach ( $allowed_products as $posts )
	{
		$allowed_ids = array_merge( $allowed_ids, $posts );
	}

	foreach ( $restricted_products as $posts )
	{
		$restricted_ids = array_merge( $restricted_ids, $posts );
	}

	// array_merge doesn't preserve keys
	$allowed    = array_unique( $allowed_ids );
	$restricted = array_unique( $restricted_ids );

	// Remove from the set of restricted posts the posts that the user is
	// definitely allowed to access
	$union = array_diff( $restricted, $allowed );

	return empty( $union ) ? array() : array_combine( $union, $union );
}

/**
 * Prevents user from directly viewing a post
 *
 */
function memberful_audit_request( $request_args )
{
	$ids = memberful_user_disallowed_post_ids();

	if ( ! empty( $request_args['p'] ) )
	{
		if ( isset( $ids[$request_args['p']] ) ) { 
			$request_args['error'] = '404';
		}
	}
	// If this isn't the homepage
	elseif ( ! empty( $request_args ) )
	{
		$request_args['post__not_in'] = $ids;
	}

	return $request_args;
}

function memberful_filter_posts( $query )
{
	$ids = memberful_user_disallowed_post_ids();

	$query->set( 'post__not_in', $ids );
}

/**
 * Gets the array of products the member with $member_id owns
 *
 * @return array member's products
 */
function memberful_get_member_products( $member_id ) {
	return get_user_meta( $member_id, 'memberful_products', TRUE );
}

/**
 * Gets the array of subscriptions that the member with $member_id owns
 *
 * @return array member's subscriptions
 */
function memberful_get_member_subscriptions( $member_id ) {
	return get_user_meta( $member_id, 'memberful_subscriptions', TRUE );
}

/**
 * Gets the array of products the current member
 *
 * @return array current member's products
 */
function memberful_get_current_user_products() {
	$current_user = wp_get_current_user();
	return memberful_get_member_products( $current_user->ID );
}

/**
 * Check if the member has the specified subscription, and that the subscription
 * has not expired.
 *
 * @return boolean
 */
function memberful_member_has_subscription( $member_id, $subscription_id ) {
	$subscriptions = memberful_get_member_subscriptions( $member_id );

	if ( empty( $subscriptions[$subscription_id] ) )
		return false;

	$subscription = $subscriptions[$subscription_id];

	return $subscription['expires_at'] === true || $subscription['expires_at'] > time();
}

/**
 * Checks that the current user has at least one of the products in the list provided
 *
 * @param $product_ids array
 */
function memberful_current_user_has_products( array $product_ids ) {
	$products = memberful_get_current_user_products();

	foreach ( $product_ids as $product_id ) {
		if ( ! empty( $products[$product_id] ) )
			return TRUE;
	}

	return FALSE;
}

/**
 * Check if the user who's currently logged in has the specified subscription
 *
 * @return boolean
 */
function memberful_current_member_has_subscription( $subscription_id ) {
	$current_user = wp_get_current_user();

	return memberful_is_member_active( $current_user->ID );
}
