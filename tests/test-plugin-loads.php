<?php
/**
 * Smoke tests that confirm the plugin loads and wires up inside WordPress.
 *
 * @package Memberful
 */

/**
 * Class Tests_Plugin_Loads
 */
class Tests_Plugin_Loads extends WP_UnitTestCase {

  public function test_plugin_version_constant_is_defined() {
    $this->assertTrue( defined( 'MEMBERFUL_VERSION' ) );
  }

  public function test_content_protection_filter_is_registered() {
    // src/content_filter.php hooks memberful_wp_protect_content onto the_content at priority 100.
    $this->assertSame( 100, has_action( 'the_content', 'memberful_wp_protect_content' ) );
  }

  public function test_memberful_shortcode_is_registered() {
    $this->assertTrue( shortcode_exists( 'memberful' ) );
  }
}
