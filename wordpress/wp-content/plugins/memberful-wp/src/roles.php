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
  $mapped_users = Memberful_User_Mapping_Repository::fetch_user_ids_of_all_mapped_members();
  $role_decision = Memberful_Wp_User_Role_Decision::build(array($old_active_role, $old_inactive_role));

  $mapped_users = get_users(array('fields' => 'all', 'include' => $mapped_users));

  foreach( $mapped_users as $user ) {
    $role_decision->update_user_role( $user );
  }
}

/**
 * Get the role mapping for a specific plan
 * @param int $plan_id The plan ID
 * @return string The WordPress role for this plan, or null if not mapped
 */
function memberful_wp_get_plan_role_mapping( $plan_id ) {
  $mappings = get_option( 'memberful_plan_role_mappings', array() );
  return isset( $mappings[ $plan_id ] ) ? $mappings[ $plan_id ] : null;
}

/**
 * Set the role mapping for a specific plan
 * @param int $plan_id The plan ID
 * @param string $role The WordPress role to assign
 */
function memberful_wp_set_plan_role_mapping( $plan_id, $role ) {
  $mappings = get_option( 'memberful_plan_role_mappings', array() );
  $mappings[ $plan_id ] = $role;
  update_option( 'memberful_plan_role_mappings', $mappings );
}

/**
 * Remove the role mapping for a specific plan
 * @param int $plan_id The plan ID
 */
function memberful_wp_remove_plan_role_mapping( $plan_id ) {
  $mappings = get_option( 'memberful_plan_role_mappings', array() );
  unset( $mappings[ $plan_id ] );
  update_option( 'memberful_plan_role_mappings', $mappings );
}

/**
 * Get all plan role mappings
 * @return array Array of plan_id => role mappings
 */
function memberful_wp_get_all_plan_role_mappings() {
  return get_option( 'memberful_plan_role_mappings', array() );
}

/**
 * Check if per-plan roles are enabled
 * @return bool
 */
function memberful_wp_use_per_plan_roles() {
  return get_option( 'memberful_use_per_plan_roles', FALSE );
}

/**
 * Enable or disable per-plan roles
 * @param bool $enabled
 */
function memberful_wp_set_use_per_plan_roles( $enabled ) {
  update_option( 'memberful_use_per_plan_roles', $enabled );
}

/**
 * Update all existing users with the new plan role mappings
 */
function memberful_wp_update_all_user_roles_with_plan_mappings() {
  $mapped_users = Memberful_User_Mapping_Repository::fetch_user_ids_of_all_mapped_members();
  
  if ( empty( $mapped_users ) ) {
    return;
  }

  $users = get_users( array( 'fields' => 'all', 'include' => $mapped_users ) );

  foreach ( $users as $user ) {
    Memberful_Wp_User_Role_Decision::ensure_user_role_is_correct( $user );
  }
}
