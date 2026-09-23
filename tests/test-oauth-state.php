<?php
/**
 * Tests for tying a Memberful sign-in to the browser that started it.
 *
 * @package Memberful
 */

/**
 * Class Tests_Oauth_State
 */
class Tests_Oauth_State extends WP_UnitTestCase {

  /**
   * A state in the format the plugin issues: 32 hex characters.
   */
  const STATE = '5f0b8c1e9d2a4b6c8e0f1a3b5c7d9e1f';

  /**
   * Leave no cookie behind for the next test.
   */
  public function tear_down() {
    unset( $_COOKIE[ Memberful_Oauth_State::COOKIE_KEY ] );

    parent::tear_down();
  }

  /**
   * A sign-in carrying the state this browser was issued started here.
   */
  public function test_matches_the_state_this_browser_was_issued() {
    $_COOKIE[ Memberful_Oauth_State::COOKIE_KEY ] = self::STATE;

    $this->assertTrue( Memberful_Oauth_State::matches( self::STATE ) );
  }

  /**
   * A sign-in carrying some other state started somewhere else.
   */
  public function test_does_not_match_a_different_state() {
    $_COOKIE[ Memberful_Oauth_State::COOKIE_KEY ] = self::STATE;

    $this->assertFalse( Memberful_Oauth_State::matches( 'a0000000000000000000000000000000' ) );
  }

  /**
   * A browser that was never issued a state didn't start the sign-in.
   */
  public function test_does_not_match_when_this_browser_was_issued_no_state() {
    $this->assertFalse( Memberful_Oauth_State::matches( self::STATE ) );
  }

  /**
   * A sign-in without a state didn't start here, whatever the browser holds.
   */
  public function test_does_not_match_a_missing_state() {
    $_COOKIE[ Memberful_Oauth_State::COOKIE_KEY ] = self::STATE;

    $this->assertFalse( Memberful_Oauth_State::matches( null ) );
  }

  /**
   * An empty state doesn't match an empty cookie.
   */
  public function test_does_not_match_an_empty_state_against_an_empty_cookie() {
    $_COOKIE[ Memberful_Oauth_State::COOKIE_KEY ] = '';

    $this->assertFalse( Memberful_Oauth_State::matches( '' ) );
  }

  /**
   * A state that isn't a string, like an array from `state[]=`, never matches.
   */
  public function test_does_not_match_a_state_that_is_not_a_string() {
    $_COOKIE[ Memberful_Oauth_State::COOKIE_KEY ] = self::STATE;

    $this->assertFalse( Memberful_Oauth_State::matches( array( self::STATE ) ) );
  }
}
