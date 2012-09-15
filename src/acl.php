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
function memberful_user_products( $user_id ) {
	return get_user_meta( $user_id, 'memberful_products', TRUE );
}

/**
 * Gets the array of subscriptions that the member with $member_id owns
 *
 * @return array member's subscriptions
 */
function memberful_user_subscriptions( $user_id ) {
	return get_user_meta( $user_id, 'memberful_subscriptions', TRUE );
}

/**
 * Gets the array of products the current member
 *
 * @return array current member's products
 */
function memberful_current_user_products() {
	$current_user = wp_get_current_user();
	return memberful_user_products( $current_user->ID );
}

/**
 * Check that the specified user has at least one of a set of subscriptions
 *
 * @param int   $user_id The id of the wordpress user
 * @param array $subscriptions Ids of the subscriptions to restrict access to
 * @return boolean
 */
function memberful_user_has_subscriptions( $user_id, array $subscriptions ) {
	$user_subs = memberful_user_subscriptions( $user_id );

	foreach ( $subscriptions as $subscription ) { 
		if ( isset( $user_subs[$subscription] ) ) {
			if( $subscription['expires_at'] === true || $subscription['expires_at'] > time() )
				return TRUE;
		}
	}

	return FALSE;
}

function memberful_user_has_products( $user_id, array $products ) {
	$user_products = memberful_user_products( $user_id );

	foreach ( $products as $product ) { 
		if ( isset( $user_products[$product] ) )
			return TRUE;
	}

	return FALSE;
}

/**
 * Check that the current member has at least one of the specified subscriptions
 *
 * @param string $slug Slug of the subscription the member should have
 * @return bool
 */
function has_memberful_subscription() {
	return memberful_user_has_subscriptions(
		wp_get_current_user()->ID,
		func_get_args()
	);
}

/**
 * Check that the current member has at least one of the specified products
 *
 * @param string $slug Slug of the product the member should have
 * @return bool
 */
function has_memberful_product() {
	return memberful_user_has_products(
		wp_get_current_user()->ID,
		funct_get_args()
	);
}
