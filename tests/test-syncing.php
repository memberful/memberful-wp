<?php
/**
 * Tests for the users Memberful syncs.
 *
 * @package Memberful
 */

/**
 * Class Tests_Syncing
 */
class Tests_Syncing extends WP_UnitTestCase {

  /**
   * An administrator is never deleted, even without any posts.
   */
  public function test_does_not_delete_an_administrator_without_content() {
    $user = $this->factory->user->create_and_get( array( 'role' => 'administrator' ) );

    $this->assertFalse( memberful_is_safe_to_delete( $user ) );
  }
}
