<?php
/**
 * Tests for when src/paywall/renderer.php loads the paywall stylesheet.
 *
 * @package Memberful
 */

/**
 * Class Tests_Paywall_Styles
 */
class Tests_Paywall_Styles extends WP_UnitTestCase {

  /**
   * Reset the static stylesheet flag and the paywall options between tests.
   */
  public function set_up() {
    parent::set_up();

    $flag = new ReflectionProperty( 'Memberful_Paywall_Renderer', 'should_print_styles' );
    $flag->setAccessible( true );
    $flag->setValue( null, false );

    wp_dequeue_style( 'memberful-paywall' );
    delete_option( 'memberful_paywall_config' );
    delete_option( 'memberful_global_marketing_content' );
  }

  /**
   * The builder paywall renders through global marketing, so a protected render loads its stylesheet.
   */
  public function test_loads_styles_when_global_marketing_is_on() {
    update_option( 'memberful_paywall_config', array( 'mode' => 'builder' ) );
    update_option( 'memberful_use_global_marketing', 1 );

    Memberful_Paywall_Renderer::protect_content( '<p>Paywall</p>' );
    Memberful_Paywall_Renderer::maybe_print_styles();

    $this->assertTrue( wp_style_is( 'memberful-paywall', 'enqueued' ) );
  }

  /**
   * Without global marketing there is no builder paywall, so sites that never used it don't get its stylesheet, whose
   * teaser fade 1.81.0 never applied to them.
   */
  public function test_skips_styles_when_global_marketing_is_off() {
    update_option( 'memberful_use_global_marketing', 0 );

    Memberful_Paywall_Renderer::protect_content( '<p>Per-post marketing content</p>' );
    Memberful_Paywall_Renderer::maybe_print_styles();

    $this->assertFalse( wp_style_is( 'memberful-paywall', 'enqueued' ) );
  }
}
