<?php
/**
 * Tests for the roles WordPress users hold.
 *
 * @package Memberful
 */

/**
 * Class Tests_Roles
 */
class Tests_Roles extends WP_UnitTestCase {

  /**
   * An editor can publish anyone's posts, so they hold a privileged role.
   */
  public function test_counts_an_editor_as_privileged() {
    $user = $this->factory->user->create_and_get( array( 'role' => 'editor' ) );

    $this->assertTrue( memberful_wp_user_is_privileged( $user ) );
  }

  /**
   * A forum role, which bbPress gives every user alongside their own, doesn't make them privileged.
   */
  public function test_does_not_count_a_subscriber_with_a_forum_role_as_privileged() {
    $forum_capabilities = array(
      'participate' => true,
      'spectate'    => true,
    );

    add_role( 'bbp_participant', 'Participant', $forum_capabilities );

    $user = $this->factory->user->create_and_get( array( 'role' => 'subscriber' ) );
    $user->add_role( 'bbp_participant' );

    $this->assertFalse( memberful_wp_user_is_privileged( $user ) );
  }
}
