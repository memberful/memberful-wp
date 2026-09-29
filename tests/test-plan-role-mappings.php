<?php
/**
 * Tests for moving members between roles when the plan role mappings change.
 *
 * @package Memberful
 */

/**
 * Class Tests_Plan_Role_Mappings
 */
class Tests_Plan_Role_Mappings extends WP_UnitTestCase {

  /**
   * The plan the member subscribes to.
   */
  const PLAN_ID = 1001;

  /**
   * Map the plan to the author role.
   */
  public function set_up() {
    parent::set_up();

    memberful_wp_set_use_per_plan_roles( true );
    update_option( 'memberful_plan_role_mappings', array( self::PLAN_ID => 'author' ) );
  }

  /**
   * Create a linked member who subscribes to the plan and holds its role.
   *
   * @return WP_User
   */
  private function member_on_the_plan() {
    $user   = $this->factory->user->create_and_get( array( 'role' => 'author' ) );
    $member = (object) array( 'id' => 990000 + $user->ID );

    ( new Memberful_User_Mapping_Repository() )->create_mapping( $user, $member, array() );
    update_user_meta( $user->ID, 'memberful_subscription', array( self::PLAN_ID => array( 'subscription' => array( 'id' => self::PLAN_ID ) ) ) );

    return $user;
  }

  /**
   * Members move to the plan's new role when its mapping changes.
   */
  public function test_moves_members_to_a_plans_new_role() {
    $user = $this->member_on_the_plan();

    update_option( 'memberful_plan_role_mappings', array( self::PLAN_ID => 'contributor' ) );
    memberful_wp_update_all_user_roles_with_plan_mappings( array( 'author' ) );

    $this->assertSame( array( 'contributor' ), get_user_by( 'id', $user->ID )->roles );
  }

  /**
   * Members move back to the active customer role when per-plan roles are turned off.
   */
  public function test_moves_members_to_the_active_role_when_per_plan_roles_are_turned_off() {
    $user = $this->member_on_the_plan();

    memberful_wp_set_use_per_plan_roles( false );
    update_option( 'memberful_plan_role_mappings', array() );
    memberful_wp_update_all_user_roles_with_plan_mappings( array( 'author' ) );

    $this->assertSame( array( memberful_wp_role_for_active_customer() ), get_user_by( 'id', $user->ID )->roles );
  }
}
