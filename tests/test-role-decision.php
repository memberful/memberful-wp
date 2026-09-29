<?php
/**
 * Tests for the roles Memberful assigns to the users it syncs.
 *
 * @package Memberful
 */

/**
 * Class Tests_Role_Decision
 */
class Tests_Role_Decision extends WP_UnitTestCase {

  /**
   * A plan whose members get the author role.
   */
  const AUTHOR_PLAN_ID = 1001;

  /**
   * Map the plan to the author role, the way the per-plan roles settings do.
   */
  public function set_up() {
    parent::set_up();

    memberful_wp_set_use_per_plan_roles( true );
    update_option( 'memberful_plan_role_mappings', array( self::AUTHOR_PLAN_ID => 'author' ) );
  }

  /**
   * Create a user with the given role and a subscription to the given plan.
   *
   * @param string $role    The role to give them.
   * @param int    $plan_id The plan they subscribe to.
   * @return WP_User
   */
  private function subscriber_to( $role, $plan_id ) {
    $user_id = $this->factory->user->create( array( 'role' => $role ) );

    update_user_meta( $user_id, 'memberful_subscription', array( $plan_id => array( 'subscription' => array( 'id' => $plan_id ) ) ) );

    return get_user_by( 'id', $user_id );
  }

  /**
   * Buying a plan must not demote an administrator to the plan's role.
   */
  public function test_keeps_an_administrator_who_buys_a_plan_as_administrator() {
    $user = $this->subscriber_to( 'administrator', self::AUTHOR_PLAN_ID );

    Memberful_Wp_User_Role_Decision::ensure_user_role_is_correct( $user );

    $this->assertSame( array( 'administrator' ), get_user_by( 'id', $user->ID )->roles );
  }

  /**
   * A plan's role replaces a role another plugin gave the member, such as WooCommerce's customer role.
   */
  public function test_gives_a_member_with_another_plugins_role_their_plans_role() {
    add_role( 'customer', 'Customer', array( 'read' => true ) );

    $user = $this->subscriber_to( 'customer', self::AUTHOR_PLAN_ID );

    Memberful_Wp_User_Role_Decision::ensure_user_role_is_correct( $user );

    $this->assertSame( array( 'author' ), get_user_by( 'id', $user->ID )->roles );
  }
}
