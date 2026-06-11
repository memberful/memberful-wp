<?php
/**
 * Tests for the shortcode-argument helpers in src/shortcodes.php.
 *
 * @package Memberful
 */

/**
 * Class ShortcodeArgsTest
 */
class ShortcodeArgsTest extends WP_UnitTestCase {

  public function test_extract_id_from_slug_with_name() {
    $this->assertSame( 123, memberful_wp_extract_id_from_slug( '123-some-plan' ) );
  }

  public function test_extract_id_from_slug_without_name() {
    $this->assertSame( 456, memberful_wp_extract_id_from_slug( '456' ) );
  }

  public function test_normalize_maps_legacy_has_subscription_alias() {
    $args = memberful_wp_normalize_shortcode_args( array( 'has_subscription' => 'gold' ) );
    $this->assertSame( 'gold', $args['has_subscription_to'] );
  }

  public function test_normalize_maps_legacy_has_product_alias() {
    $args = memberful_wp_normalize_shortcode_args( array( 'has_product' => '10-ebook' ) );
    $this->assertSame( '10-ebook', $args['has_download'] );
  }
}
