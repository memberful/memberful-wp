<?php

function memberful_wp_roles_that_can_be_mapped_to() {
	global $wp_roles;

	$allowed_roles = $wp_roles->get_names();

	unset($allowed_roles['administrator']);

	return $allowed_roles;
}

function memberful_wp_role_for_active_customer( $default_role = 'subscriber' ) {
	$configured_role = get_option( 'memberful_role_active_customer', $default_role );

	if ( array_key_exists( $configured_role, memberful_wp_roles_that_can_be_mapped_to() ) )
		return $configured_role;

	return $default_role;
}

function memberful_wp_role_for_inactive_customer( $default_role = 'subscriber' ) {
	$configured_role = get_option( 'memberful_role_inactive_customer', $default_role );

	if ( array_key_exists( $configured_role, memberful_wp_roles_that_can_be_mapped_to() ) )
		return $configured_role;

	return $default_role;
}


function memberful_wp_update_customer_roles( $old_active_role, $new_active_role, $old_inactive_role, $new_inactive_role ) {
	$mapped_users = Memberful_User_Map::fetch_user_ids_of_all_mapped_members();

	$old_active_users = get_users(array('fields' => 'all', 'role' => $old_active_role, 'include' => $mapped_users));

	foreach( $old_active_users as $user ) {
		$user->set_role( $new_active_role );
	}

	$old_inactive_users = get_users(array('fields' => 'all', 'role' => $old_inactive_role, 'include' => $mapped_users));

	foreach( $old_inactive_users as $user ) {
		$user->set_role( $new_inactive_role );
	}
}
