<?php
/**
 * Tests for deciding which members see ads.
 *
 * @package Memberful
 */

/**
 * Class Tests_Ad_Provider_Controls
 */
class Tests_Ad_Provider_Controls extends WP_UnitTestCase {

  /**
   * The plan ads are hidden for.
   */
  const AD_FREE_PLAN_ID = 1001;

  /**
   * A plan that keeps ads.
   */
  const OTHER_PLAN_ID = 1002;

  /**
   * The provider under test.
   *
   * @var Memberful_Wp_Integration_Ad_Provider_Mediavine
   */
  private $provider;

  /**
   * Use the Mediavine provider, which relies on the shared base class checks.
   */
  public function set_up() {
    parent::set_up();

    $this->provider = new Memberful_Wp_Integration_Ad_Provider_Mediavine();
  }

  /**
   * Store settings for the Mediavine provider.
   *
   * @param array $settings Settings merged over an enabled provider with no options turned on.
   */
  private function set_settings( array $settings ) {
    update_option(
      'memberful_ad_provider_settings',
      array(
        'mediavine-ads' => array_merge(
          array(
            'enabled'                     => true,
            'disabled_plans'              => array(),
            'disable_for_all_subscribers' => false,
            'disable_for_logged_in'       => false,
          ),
          $settings
        ),
      )
    );
  }

  /**
   * Create a member with active subscriptions to the given plans, stored as syncing stores them.
   *
   * @param array $plan_ids The plans the member subscribes to.
   * @return int The member's user ID.
   */
  private function member_subscribed_to( array $plan_ids ) {
    $user_id       = $this->factory->user->create();
    $subscriptions = array();

    foreach ( $plan_ids as $plan_id ) {
      $subscriptions[ $plan_id ] = array(
        'activated_at'    => time(),
        'autorenew'       => true,
        'expires'         => false,
        'expires_at'      => time() + DAY_IN_SECONDS,
        'id'              => $plan_id,
        'in_trial_period' => false,
        'trial_start_at'  => null,
        'trial_end_at'    => null,
      );
    }

    update_user_meta( $user_id, 'memberful_subscription', $subscriptions );

    return $user_id;
  }

  /**
   * Ads are hidden for a member subscribed to a selected plan.
   */
  public function test_hides_ads_for_a_member_of_a_selected_plan() {
    $this->set_settings( array( 'disabled_plans' => array( self::AD_FREE_PLAN_ID ) ) );

    $this->assertTrue( $this->provider->should_disable_ads_for_user( $this->member_subscribed_to( array( self::AD_FREE_PLAN_ID ) ) ) );
  }

  /**
   * Ads are hidden when one of the member's several plans is selected.
   */
  public function test_hides_ads_when_any_of_a_members_plans_is_selected() {
    $this->set_settings( array( 'disabled_plans' => array( self::AD_FREE_PLAN_ID ) ) );

    $this->assertTrue( $this->provider->should_disable_ads_for_user( $this->member_subscribed_to( array( self::OTHER_PLAN_ID, self::AD_FREE_PLAN_ID ) ) ) );
  }

  /**
   * Ads stay for a member whose plans are not selected.
   */
  public function test_shows_ads_for_a_member_of_an_unselected_plan() {
    $this->set_settings( array( 'disabled_plans' => array( self::AD_FREE_PLAN_ID ) ) );

    $this->assertFalse( $this->provider->should_disable_ads_for_user( $this->member_subscribed_to( array( self::OTHER_PLAN_ID ) ) ) );
  }

  /**
   * Ads stay for a user without a subscription when plans are selected.
   */
  public function test_shows_ads_for_a_user_without_a_subscription_when_plans_are_selected() {
    $this->set_settings( array( 'disabled_plans' => array( self::AD_FREE_PLAN_ID ) ) );

    $this->assertFalse( $this->provider->should_disable_ads_for_user( $this->factory->user->create() ) );
  }

  /**
   * Ads return once the member's subscription to the selected plan ends.
   */
  public function test_shows_ads_after_a_members_selected_plan_ends() {
    $this->set_settings( array( 'disabled_plans' => array( self::AD_FREE_PLAN_ID ) ) );
    $user_id = $this->member_subscribed_to( array( self::AD_FREE_PLAN_ID ) );
    $this->provider->should_disable_ads_for_user( $user_id );

    update_user_meta( $user_id, 'memberful_subscription', array() );

    $this->assertFalse( $this->provider->should_disable_ads_for_user( $user_id ) );
  }

  /**
   * Ads stay when the provider is not enabled, even for a member of a selected plan.
   */
  public function test_shows_ads_when_the_provider_is_not_enabled() {
    $this->set_settings(
      array(
        'enabled'        => false,
        'disabled_plans' => array( self::AD_FREE_PLAN_ID ),
      )
    );

    $this->assertFalse( $this->provider->should_disable_ads_for_user( $this->member_subscribed_to( array( self::AD_FREE_PLAN_ID ) ) ) );
  }

  /**
   * Ads are hidden for a member of any plan when all subscribers are ad-free.
   */
  public function test_hides_ads_for_any_subscriber_when_all_subscribers_are_ad_free() {
    $this->set_settings( array( 'disable_for_all_subscribers' => true ) );

    $this->assertTrue( $this->provider->should_disable_ads_for_user( $this->member_subscribed_to( array( self::OTHER_PLAN_ID ) ) ) );
  }

  /**
   * Ads stay for a user without a subscription when all subscribers are ad-free.
   */
  public function test_shows_ads_for_a_user_without_a_subscription_when_all_subscribers_are_ad_free() {
    $this->set_settings( array( 'disable_for_all_subscribers' => true ) );

    $this->assertFalse( $this->provider->should_disable_ads_for_user( $this->factory->user->create() ) );
  }

  /**
   * Ads are hidden for a logged-in user when logged-in users are ad-free.
   */
  public function test_hides_ads_for_a_logged_in_user_when_logged_in_users_are_ad_free() {
    $this->set_settings( array( 'disable_for_logged_in' => true ) );
    $user_id = $this->factory->user->create();
    wp_set_current_user( $user_id );

    $this->assertTrue( $this->provider->should_disable_ads_for_user( $user_id ) );
  }

  /**
   * Ads stay for a visitor when logged-in users are ad-free.
   */
  public function test_shows_ads_for_a_visitor_when_logged_in_users_are_ad_free() {
    $this->set_settings( array( 'disable_for_logged_in' => true ) );
    wp_set_current_user( 0 );

    $this->assertFalse( $this->provider->should_disable_ads_for_user( 0 ) );
  }

  /**
   * The filter overrides the settings.
   */
  public function test_filter_overrides_the_settings() {
    $this->set_settings( array( 'disabled_plans' => array( self::AD_FREE_PLAN_ID ) ) );
    add_filter( 'memberful_ad_provider_should_disable_ads_for_user', '__return_false' );

    $this->assertFalse( $this->provider->should_disable_ads_for_user( $this->member_subscribed_to( array( self::AD_FREE_PLAN_ID ) ) ) );
  }
}
