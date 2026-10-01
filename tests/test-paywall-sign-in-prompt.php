<?php
/**
 * Tests for the "Already a subscriber? Sign in" prompt in src/paywall/renderer.php.
 *
 * @package Memberful
 */

/**
 * Class Tests_Paywall_Sign_In_Prompt
 */
class Tests_Paywall_Sign_In_Prompt extends WP_UnitTestCase {

  /**
   * Logged-out visitors get the sign-in link.
   */
  public function test_shows_sign_in_link_to_logged_out_visitors() {
    $html = Memberful_Paywall_Renderer::render( array() );

    $this->assertStringContainsString( 'memberful-paywall__signin', $html );
    $this->assertStringContainsString( '<a class="memberful-paywall__signin-link"', $html );
  }

  /**
   * Logged-in users are already signed in, so the prompt is left out.
   */
  public function test_hides_sign_in_prompt_from_logged_in_users() {
    wp_set_current_user( self::factory()->user->create() );

    $this->assertStringNotContainsString( 'memberful-paywall__signin', Memberful_Paywall_Renderer::render( array() ) );
  }

  /**
   * The admin preview keeps the prompt so it can be styled, even though the admin is logged in.
   */
  public function test_admin_preview_keeps_sign_in_prompt() {
    wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

    $this->assertStringContainsString( 'memberful-paywall__signin', Memberful_Paywall_Renderer::render( array(), false ) );
  }
}
