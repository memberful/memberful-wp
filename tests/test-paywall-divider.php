<?php
/**
 * Tests for the paywall-divider parsing in src/content_filter.php.
 *
 * @package Memberful
 */

/**
 * Class PaywallDividerTest
 */
class PaywallDividerTest extends WP_UnitTestCase {

  public function test_splits_content_at_divider_marker() {
    $marker = memberful_wp_get_paywall_divider_marker();
    $result = memberful_wp_split_post_content_at_paywall_divider( 'teaser' . $marker . 'members only' );

    $this->assertTrue( $result['has_divider'] );
    $this->assertSame( 'teaser', $result['content_above_divider'] );
    $this->assertSame( 'members only', $result['content_below_divider'] );
  }

  public function test_content_without_marker_has_no_divider() {
    $result = memberful_wp_split_post_content_at_paywall_divider( 'just content' );

    $this->assertFalse( $result['has_divider'] );
    $this->assertSame( 'just content', $result['content_above_divider'] );
  }
}
