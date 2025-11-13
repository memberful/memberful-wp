<?php

function memberful_wp_roles_that_can_be_mapped_to() {
  global $wp_roles;

  $allowed_roles = $wp_roles->get_names();

  unset($allowed_roles['administrator']);

  /**
   * Filter to determine the allowed roles that can be mapped to.
   *
   * @since 1.77.0
   *
   * @param array $allowed_roles The allowed roles that can be mapped to.
   * @return array The allowed roles that can be mapped to.
   */
  $allowed_roles = apply_filters( 'memberful_allowed_roles_for_mapping', $allowed_roles );

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
  $mappings = memberful_wp_get_all_plan_role_mappings();
  return isset( $mappings[ $plan_id ] ) ? $mappings[ $plan_id ] : null;
}

/**
 * Set the role mapping for a specific plan
 * @param int $plan_id The plan ID
 * @param string $role The WordPress role to assign
 */
function memberful_wp_set_plan_role_mapping( $plan_id, $role ) {
  $mappings = memberful_wp_get_all_plan_role_mappings();
  $mappings[ $plan_id ] = $role;
  update_option( 'memberful_plan_role_mappings', $mappings );
}

/**
 * Remove the role mapping for a specific plan
 * @param int $plan_id The plan ID
 */
function memberful_wp_remove_plan_role_mapping( $plan_id ) {
  $mappings = memberful_wp_get_all_plan_role_mappings();
  unset( $mappings[ $plan_id ] );
  update_option( 'memberful_plan_role_mappings', $mappings );
}

/**
 * Get all plan role mappings
 * @return array Array of plan_id => role mappings
 */
function memberful_wp_get_all_plan_role_mappings() {
  $mappings = get_option( 'memberful_plan_role_mappings', array() );

  if ( ! is_array( $mappings ) ) {
    $mappings = array();
  }

  $allowed_roles = memberful_wp_roles_that_can_be_mapped_to();

  foreach ( $mappings as $plan_id => $role ) {
    if ( empty( $role ) || ! array_key_exists( $role, $allowed_roles ) ) {
      $mappings[ $plan_id ] = get_option( 'default_role', 'subscriber' );
    }
  }

  /**
   * Filter to get all the saved per-plan role mappings.
   *
   * @since 1.77.0
   *
   * @param array $mappings The saved per-plan role mappings.
   * @return array The role mappings.
   */
  return apply_filters( 'memberful_all_plan_role_mappings', $mappings );
}

/**
 * Check if per-plan roles are enabled
 * @return bool
 */
function memberful_wp_use_per_plan_roles() {
  $use_per_plan_roles = get_option( 'memberful_use_per_plan_roles', FALSE );

  /**
   * Filter to determine if per-plan roles are enabled.
   *
   * @since 1.77.0
   *
   * @param bool $use_per_plan_roles Whether per-plan roles are enabled.
   * @return bool Whether per-plan roles are enabled. (Default: false)
   */
  return apply_filters( 'memberful_use_per_plan_roles', $use_per_plan_roles );
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

/**
 * Get the assigned role for a user.
 *
 * @param WP_User $user The user to get the role for.
 * @return string|WP_Error The assigned role for the user, or a WP_Error if the user is invalid.
 */
function memberful_wp_user_role_for_user( WP_User $user ) {
  if ( ! $user instanceof WP_User ) {
    return get_option( 'default_role', 'subscriber' );
  }

  $role_decision = Memberful_Wp_User_Role_Decision::build();

  $user_role = $role_decision->role_for_user( reset( $user->roles ), memberful_wp_user_plans_subscribed_to( $user->ID ) );

  /**
   * Filter to determine the user role for a user.
   *
   * @since 1.77.0
   *
   * @param string $user_role The user role for the user.
   * @param WP_User $user The user.
   * @return string The user role for the user.
   */
  return apply_filters( 'memberful_wp_user_role_for_user', $user_role, $user );
}
