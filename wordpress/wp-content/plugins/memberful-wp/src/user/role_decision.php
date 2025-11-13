<?php

class Memberful_Wp_User_Role_Decision {
  public static function ensure_user_role_is_correct( WP_User $user ) {
    $decision = self::build();

    $decision->update_user_role( $user );

    clean_user_cache( $user->ID );
  }

  public static function build(array $extra_roles_memberful_is_allowed_to_change_from = array()) {
    return new Memberful_Wp_User_Role_Decision(
      get_option( 'memberful_role_active_customer', 'subscriber' ),
      get_option( 'memberful_role_inactive_customer', 'subscriber' ),
      get_option( 'default_role', 'subscriber' ),
      $extra_roles_memberful_is_allowed_to_change_from
    );
  }

  private $active_role;
  private $inactive_role;
  private $roles_memberful_is_allowed_to_change_from;

  public function __construct( $active_role, $inactive_role, $default_role, array $extra_roles_memberful_is_allowed_to_change_from = array() ) {
    /**
     * Filter to determine the active customer role.
     *
     * @since 1.77.0
     *
     * @param string $active_role The active customer role.
     * @return string The active customer role.
     */
    $this->active_role = apply_filters( 'memberful_user_role_for_active_customer', $active_role );

    /**
     * Filter to determine the inactive customer role.
     *
     * @since 1.77.0
     *
     * @param string $inactive_role The inactive customer role.
     * @return string The inactive customer role.
     */
    $this->inactive_role = apply_filters( 'memberful_user_role_for_inactive_customer', $this->get_fallback_role_for_inactive_user( $inactive_role ) );

    $this->roles_memberful_is_allowed_to_change_from = array_merge(
      array( $this->active_role, $this->inactive_role, $default_role ),
      $extra_roles_memberful_is_allowed_to_change_from
    );
  }

  public function update_user_role( WP_User $user ) {
    $current_subscriptions = memberful_wp_user_plans_subscribed_to( $user->ID );
    $new_role = $this->role_for_user( reset( $user->roles ), $current_subscriptions );

    /**
     * Filter to determine the new role for a user.
     *
     * @since 1.77.0
     *
     * @param string $new_role The new role for the user.
     * @param WP_User $user The user.
     * @param array $current_subscriptions The current subscriptions for the user.
     *
     * @return string The new role for the user.
     */
    $new_role = apply_filters( 'memberful_user_role_for_update_user_role', $new_role, $user, $current_subscriptions );

    $user->set_role( $new_role );
  }

  public function role_for_user($current_role, $current_subscriptions) {

    /**
     * Filter to determine the current subscriptions for a user.
     *
     * @since 1.77.0
     *
     * @param array $current_subscriptions The current subscriptions for the user.
     * @return array The current subscriptions for the user.
     */
    $current_subscriptions = apply_filters( 'memberful_role_decision_user_current_subscriptions', $current_subscriptions );
    $is_active             = ! empty( $current_subscriptions );

    // If per-plan roles are enabled and the user has an active subscription,
    // use the role mapping for the user's subscriptions.
    if ( memberful_wp_use_per_plan_roles() && $is_active ) {
      return $this->role_for_user_with_plan_mappings( $current_subscriptions );
    }

    if ( ! in_array( $current_role, $this->roles_memberful_is_allowed_to_change_from ) ) {
      return $current_role;
    }

    return $is_active ? $this->active_role : $this->inactive_role;
  }

  /**
   * Determine the role for a user based on their plan subscriptions
   * @param array $current_subscriptions User's current subscription plans
   * @return string The role to assign
   */
  private function role_for_user_with_plan_mappings( $current_subscriptions ) {
    $plan_mappings = memberful_wp_get_all_plan_role_mappings();

    // Find the highest priority role based on user's subscriptions
    $assigned_roles = array();

    foreach ( $current_subscriptions as $plan_id => $subscription_data ) {
      if ( isset( $plan_mappings[ $plan_id ] ) ) {
        $assigned_roles[] = $plan_mappings[ $plan_id ];
      }
    }

    // If user has multiple plans with different roles, we need to determine priority
    if ( ! empty( $assigned_roles ) ) {
      // For now, we'll use the role with the highest level of capabilities.
      // In the future, this could be enhanced with support for multiple roles per plan.
      $role = $this->get_user_role_with_highest_capabilities( $assigned_roles );

      /**
       * Filter to determine the role for a user with plan mappings.
       *
       * @since 1.77.0
       *
       * @param string $role The role for the user.
       * @param array $current_subscriptions Current subscriptions for the user.
       * @return string The role for the user.
       */
      return apply_filters( 'memberful_user_role_for_user_with_plan_mappings', $role, $current_subscriptions );
    }

    // If no mapping is found, use the inactive role as the fallback.
    return $this->inactive_role;
  }

  /**
   * Get the inactive role for the user.
   *
   * @return string The inactive role for the user.
   */
  public function get_inactive_role() {
    return $this->inactive_role;
  }

  /**
   * Get the fallback role for the inactive user
   * @param string $default_inactive_role The default inactive role to use if no fallback is found
   * @return string The fallback role for the inactive user
   */
  private function get_fallback_role_for_inactive_user( $default_inactive_role = 'subscriber' ) {
    if ( memberful_wp_use_per_plan_roles() ) {
      $plan_mappings = memberful_wp_get_all_plan_role_mappings();

      if ( isset( $plan_mappings['inactive'] ) && ! empty( $plan_mappings['inactive'] ) ) {
        return $plan_mappings['inactive'];
      }

      return get_option( 'default_role', 'subscriber' );
    }

    return $default_inactive_role;
  }

  /**
   * Get the user role with the highest level of capabilities from a list of roles.
   *
   * This is a temporary solution to determine which of multiple roles
   * should be assigned to a user with multiple roles.
   *
   * @param array $roles The roles to check.
   * @return string The role with the highest level of capabilities.
   */
  public function get_user_role_with_highest_capabilities( $roles ) {
    global $wp_roles;

    if (!isset($wp_roles)) {
        $wp_roles = new WP_Roles();
    }

    $highest_count = 0;
    $highest_role = '';

    foreach ($roles as $role_name) {
        $role = $wp_roles->get_role($role_name);
        if ($role && $role->capabilities) {
          // Get the level_* capabilities and find the highest one that is set to true.
          $level_capabilities = array_filter($role->capabilities, function($value, $key) {
            return strpos($key, 'level_') === 0 && $value === true;
          }, ARRAY_FILTER_USE_BOTH);

          if (empty($level_capabilities)) {
            continue;
          }

          $highest_level = max(array_keys($level_capabilities));
          $highest_level = absint(str_replace('level_', '', $highest_level));

          if ($highest_level > $highest_count) {
            $highest_count = $highest_level;
            $highest_role = $role_name;
          }
        }
    }

    if ( empty( $highest_role ) && ! empty( $roles ) ) {
      // Fallback to the first role in the list if no highest role is found.
      // This can happen if the user has no roles with level_* capabilities,
      // or with custom roles that don't have level_* capabilities.
      $highest_role = reset( $roles );
    }

    return $highest_role;
  }
}
