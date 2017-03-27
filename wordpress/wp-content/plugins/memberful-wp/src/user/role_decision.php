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

  public function __construct( $active_role, $inactive_role, $default_role, array $extra_roles_memberful_is_allowed_to_change_from = array() ) {
    $this->active_role   = $active_role;
    $this->inactive_role = $inactive_role;

    $this->roles_memberful_is_allowed_to_change_from = array_merge(
      array( $active_role, $inactive_role, $default_role ),
      $extra_roles_memberful_is_allowed_to_change_from
    );
  }

  public function update_user_role( WP_User $user ) {
    $new_role = $this->role_for_user(
      reset( $user->roles ),
      memberful_wp_user_plans_subscribed_to( $user->ID )
    );

    $user->set_role( $new_role );
  }

  public function role_for_user($current_role, $current_subscriptions) {
    $is_active = ! empty( $current_subscriptions );

    if ( ! in_array( $current_role, $this->roles_memberful_is_allowed_to_change_from ) ) {
      return $current_role;
    }

    return $is_active ? $this->active_role : $this->inactive_role;
  }
}
