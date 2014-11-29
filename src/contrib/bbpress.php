<?php

add_filter( 'bbp_current_user_can_publish_topics', 'memberful_wp_prevent_access_to_unauthorized_users' );
add_filter( 'bbp_after_get_forums_for_current_user_parse_args', 'memberful_wp_restrict_forums_current_user_can_access' );


/**
 * Hooks into bbp_parse_args / bbp_get_forums_for_current_user
 */
function memberful_wp_restrict_forums_current_user_can_access( $args ) {
	$disallowed_post_ids = memberful_wp_user_disallowed_post_ids( get_current_user_id() );

	if ( empty( $args['exclude'] ) ) {
		$args['exclude'] = $disallowed_post_ids;
	} else {
		$args['exclude'] = array_merge( (array) $args['exclude'], $disallowed_post_ids );
	}

	return $args;
}

function memberful_wp_prevent_access_to_unauthorized_users( $currently_allowed ) {
	if ( ! $currently_allowed )
		return $currently_allowed;

	if ( bbp_is_user_keymaster() )
		return true;

	return FALSE;
}
