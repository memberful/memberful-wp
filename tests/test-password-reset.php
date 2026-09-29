<?php
/**
 * Tests for which users may reset their WordPress password.
 *
 * @package Memberful
 */

/**
 * Class Tests_Password_Reset
 */
class Tests_Password_Reset extends WP_UnitTestCase {

  /**
   * Whether WordPress would send the user a password reset link.
   *
   * @param WP_User $user The user asking for a reset.
   * @return bool
   */
  private function can_reset_password( $user ) {
    return apply_filters( 'allow_password_reset', true, $user->ID );
  }

  /**
   * Create a user linked to a member, as signing in through Memberful would.
   *
   * @param string $role The role to give them.
   * @return WP_User
   */
  private function linked_user( $role ) {
    $user   = $this->factory->user->create_and_get( array( 'role' => $role ) );
    $member = (object) array( 'id' => 990000 + $user->ID );

    ( new Memberful_User_Mapping_Repository() )->create_mapping( $user, $member, array() );

    return $user;
  }

  /**
   * A linked administrator can still sign in with a WordPress password, so they can reset it.
   */
  public function test_allows_a_linked_administrator_to_reset_their_password() {
    $user = $this->linked_user( 'administrator' );

    $this->assertTrue( $this->can_reset_password( $user ) );
  }

  /**
   * A member signs in through Memberful, so their WordPress password can't be reset.
   */
  public function test_prevents_a_member_from_resetting_their_password() {
    $user = $this->linked_user( 'subscriber' );

    $this->assertFalse( $this->can_reset_password( $user ) );
  }

  /**
   * A user who registered through WordPress signs in with a WordPress password, so they can reset it.
   */
  public function test_allows_a_user_who_is_not_a_member_to_reset_their_password() {
    $user = $this->factory->user->create_and_get( array( 'role' => 'subscriber' ) );

    $this->assertTrue( $this->can_reset_password( $user ) );
  }
}
