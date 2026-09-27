<?php
/**
 * Tests for the private member feed token lookup.
 *
 * @package Memberful
 */

/**
 * Class Tests_Private_User_Feed
 */
class Tests_Private_User_Feed extends WP_UnitTestCase {

  /**
   * A token in the format the plugin mints: 30 hex characters.
   */
  const TOKEN = '41f7a18dc490ba0238adacf5f45f06';

  /**
   * The plan the feed is enabled for.
   */
  const PLAN_ID = 1001;

  /**
   * The request URI the suite runs with, restored after each test.
   *
   * @var string|null
   */
  private $original_request_uri;

  /**
   * Enable the feed for a plan, the way the settings page does.
   */
  public function set_up() {
    parent::set_up();

    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- Remembered as-is so the suite gets it back untouched.
    $this->original_request_uri = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : null;

    memberful_private_user_feed_settings_set_required_plan( array( self::PLAN_ID ) );
  }

  /**
   * Leave no request state behind for the next test.
   */
  public function tear_down() {
    unset( $_GET['member-feed'] );

    if ( null === $this->original_request_uri ) {
      unset( $_SERVER['REQUEST_URI'] );
    } else {
      $_SERVER['REQUEST_URI'] = $this->original_request_uri;
    }

    parent::tear_down();
  }

  /**
   * Create a member holding a feed token and a subscription to the feed's plan.
   *
   * @param string $token The feed token to give them.
   * @return int The id of the member.
   */
  private function member_with_token( $token ) {
    $user_id       = $this->factory->user->create();
    $subscriptions = array( self::PLAN_ID => array( 'subscription' => array( 'id' => self::PLAN_ID ) ) );

    update_user_meta( $user_id, 'memberful_private_user_feed_token', $token );
    update_user_meta( $user_id, 'memberful_subscription', $subscriptions );

    return $user_id;
  }

  /**
   * The token of a member identifies that member.
   */
  public function test_finds_the_member_a_token_belongs_to() {
    $user_id = $this->member_with_token( self::TOKEN );

    $this->assertSame( $user_id, memberful_private_user_feed_user_for_token( self::TOKEN ) );
  }

  /**
   * An empty token must not match a member holding a real one.
   */
  public function test_an_empty_token_matches_no_member() {
    $this->member_with_token( self::TOKEN );

    $this->assertFalse( memberful_private_user_feed_user_for_token( '' ) );
  }

  /**
   * An empty token must not match a member whose stored token is empty either.
   */
  public function test_an_empty_token_matches_no_member_holding_an_empty_token() {
    $this->member_with_token( '' );

    $this->assertFalse( memberful_private_user_feed_user_for_token( '' ) );
  }

  /**
   * The request handler serves nothing when the feed URL carries no token.
   */
  public function test_the_feed_is_not_served_without_a_token() {
    $this->member_with_token( self::TOKEN );

    $_SERVER['REQUEST_URI'] = '/?member-feed=';
    $_GET['member-feed']    = '';

    ob_start();
    memberful_private_user_feed_init();
    $output = ob_get_clean();

    // Reaching this assertion is itself the check: delivering the feed exits.
    $this->assertSame( '', $output );
  }
}
