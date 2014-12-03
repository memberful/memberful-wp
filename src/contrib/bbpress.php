<?php

add_action( 'template_redirect', 'memberful_wp_regulate_access_to_bbpress' );

function memberful_wp_regulate_access_to_bbpress() {
	if ( ! is_bbpress() )
		return;

	if ( current_user_can( 'moderate' ) )
		return;

	if ( memberful_wp_bbpress_restricted_to_registered_users() && ! is_user_logged_in() )
		wp_safe_redirect( memberful_wp_bbpress_unauthorized_user_landing_page() );

	if ( memberful_wp_bbpress_restricted_to_customers() ) {
		$has_required_plan     = memberful_wp_user_has_subscription_to_plans( get_current_user_id(), memberful_wp_bbpress_required_plans() );
		$has_required_download = memberful_wp_user_has_downloads( get_current_user_id(), memberful_wp_bbpress_required_downloads());

		if ( $has_required_plan || $has_required_download ) {
			wp_safe_redirect( memberful_wp_bbpress_unauthorized_user_landing_page() );
		}
	}
}

function memberful_wp_bbpress_restricted_to_registered_users() {
	return get_option( 'memberful_bbpress_restricted_registered_users', FALSE );
}

function memberful_wp_bbpress_restricted_to_customers() {
	return get_option( 'memberful_bbpress_protect_forums', FALSE );
}

function memberful_wp_bbpress_required_plans() {
	return get_option( 'memberful_bbpress_required_plans', array() );
}

function memberful_wp_bbpress_required_downloads() {
	return get_option( 'memberful_bbpress_required_downloads', array() );
}
