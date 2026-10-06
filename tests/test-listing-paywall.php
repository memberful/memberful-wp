<?php
/**
 * Tests for the paywall in listings in src/content_filter.php.
 *
 * @package Memberful
 */

/**
 * Class Tests_Listing_Paywall
 */
class Tests_Listing_Paywall extends WP_UnitTestCase {

  /**
   * Marketing content attached to the protected post.
   */
  const MARKETING_CONTENT = '<div class="listing-test-paywall"><strong>Join now</strong> to keep reading.</div>';

  /**
   * Protected post used by every test. Created once, since the restricted post lookup caches per request.
   *
   * @var int
   */
  private static $post_id;

  /**
   * Create a post with its own marketing content.
   *
   * @param WP_UnitTest_Factory $factory Test factory.
   */
  public static function wpSetUpBeforeClass( $factory ) {
    self::$post_id = $factory->post->create(
      array(
        'post_status'  => 'publish',
        'post_content' => "<!-- wp:paragraph -->\n<p>Opening paragraph.</p>\n<!-- /wp:paragraph -->",
        'post_excerpt' => '',
      )
    );

    update_post_meta( self::$post_id, MEMBERFUL_MARKETING_META_KEY, self::MARKETING_CONTENT );
  }

  /**
   * Make the post readable by registered users only and view the front page logged out.
   */
  public function set_up() {
    parent::set_up();

    update_option( 'memberful_global_marketing_override', false );
    memberful_wp_set_post_available_to_any_registered_users( self::$post_id, true );

    wp_set_current_user( 0 );
    $this->go_to( home_url( '/' ) );

    $GLOBALS['post'] = get_post( self::$post_id );
    setup_postdata( $GLOBALS['post'] );
  }

  /**
   * Render the post's content through the_content.
   *
   * @return string
   */
  private function render_content(): string {
    return apply_filters( 'the_content', get_post_field( 'post_content', self::$post_id ) );
  }

  /**
   * A full-content listing shows the paywall.
   */
  public function test_shows_the_paywall_in_full_content_listings() {
    $this->assertStringContainsString( 'listing-test-paywall', $this->render_content() );
  }

  /**
   * An excerpt listing shows the teaser without the paywall, whose markup the excerpt would strip.
   */
  public function test_leaves_the_paywall_out_of_excerpts() {
    $this->assertStringNotContainsString( 'Join now', get_the_excerpt( self::$post_id ) );
  }
}
