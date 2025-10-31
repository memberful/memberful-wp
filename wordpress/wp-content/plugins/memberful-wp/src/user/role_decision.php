<?php

class Memberful_Wp_User_Role_Decision {
  public static function ensure_user_role_is_correct( WP_User $user ) {
    $decision = self::build();

    return $decision->update_user_role( $user );
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
    $this->active_role   = $active_role;
    $this->inactive_role = $this->get_fallback_role_for_inactive_user( $inactive_role );

    $this->roles_memberful_is_allowed_to_change_from = array_merge(
      array( $this->active_role, $this->inactive_role, $default_role ),
      $extra_roles_memberful_is_allowed_to_change_from
    );
  }

  public function update_user_role( WP_User $user ) {
    $current_subscriptions = memberful_wp_user_plans_subscribed_to( $user->ID );
    $new_role = $this->role_for_user( reset( $user->roles ), $current_subscriptions );

    $new_role = apply_filters( 'memberful_wp_user_role_for_update_user_role', $new_role, $user, $current_subscriptions );

    $user->set_role( $new_role );
  }

  public function role_for_user($current_role, $current_subscriptions) {
    $is_active = ! empty( $current_subscriptions );

    // If per-plan roles are enabled and the user has an active subscription,
    // use the role mapping for the user's subscriptions.
    if ( memberful_wp_use_per_plan_roles() && $is_active ) {
      return $this->role_for_user_with_plan_mappings( $current_subscriptions );
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
      // For now, we'll use the first role found
      // In the future, this could be enhanced with support for multiple roles per plan.
      return $assigned_roles[0];
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
}
