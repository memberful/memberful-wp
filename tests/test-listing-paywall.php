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
  const MARKETING_CONTENT = '<div class="listing-test-paywall"><strong>Join now</strong> to keep reading. [memberful_sign_in_link]Login[/memberful_sign_in_link] to see the full update.</div>';

  /**
   * Protected post used by every test. Created once, since the restricted post lookup caches per request.
   *
   * @var int
   */
  private static $post_id;

  /**
   * Post protected through its category, which has marketing content of its own.
   *
   * @var int
   */
  private static $term_post_id;

  /**
   * Category that restricts the second post.
   *
   * @var int
   */
  private static $term_id;

  /**
   * Create a post with its own marketing content, and one restricted by a category with marketing content.
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

    self::$term_id      = $factory->category->create();
    self::$term_post_id = $factory->post->create(
      array(
        'post_status'   => 'publish',
        'post_content'  => "<!-- wp:paragraph -->\n<p>Category post body.</p>\n<!-- /wp:paragraph -->",
        'post_excerpt'  => '',
        'post_category' => array( self::$term_id ),
      )
    );

    update_term_meta( self::$term_id, MEMBERFUL_MARKETING_META_KEY, '<p>Category preview for members.</p>' );
  }

  /**
   * Make the post and the category readable by registered users only and view the front page logged out.
   */
  public function set_up() {
    parent::set_up();

    update_option( 'memberful_global_marketing_override', false );
    memberful_wp_set_post_available_to_any_registered_users( self::$post_id, true );
    memberful_wp_set_term_available_to_any_registered_users( self::$term_id, true );

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
   * An excerpt listing shows the marketing content written for the post, with its shortcodes rendered, and none of the
   * protected body.
   */
  public function test_shows_post_marketing_content_in_excerpts() {
    $excerpt = get_the_excerpt( self::$post_id );

    $this->assertSame( 'Join now to keep reading. Login to see the full update.', $excerpt );
    $this->assertStringNotContainsString( 'Opening paragraph', $excerpt );
  }

  /**
   * An excerpt listing shows the marketing content of the term that restricts the post.
   */
  public function test_shows_term_marketing_content_in_excerpts() {
    $GLOBALS['post'] = get_post( self::$term_post_id );
    setup_postdata( $GLOBALS['post'] );

    $this->assertSame( 'Category preview for members.', get_the_excerpt( self::$term_post_id ) );
  }

  /**
   * Global marketing content set to override the post's own stays out of excerpts along with the content it replaces.
   */
  public function test_leaves_overriding_global_marketing_content_out_of_excerpts() {
    $this->use_global_marketing( true );
    update_option( 'memberful_global_marketing_content', '<p>Global lock screen.</p>' );

    $this->assertSame( '', get_the_excerpt( self::$post_id ) );
  }

  /**
   * The builder paywall stays out of excerpts, where WordPress would strip its markup, and so does its stylesheet.
   */
  public function test_leaves_the_builder_paywall_out_of_excerpts() {
    $this->use_global_marketing( false );
    update_option( 'memberful_paywall_config', array( 'mode' => 'builder' ) );
    delete_post_meta( self::$post_id, MEMBERFUL_MARKETING_META_KEY );

    $this->assertStringContainsString( 'memberful-paywall__heading', $this->render_content() );
    $this->assertSame( '', get_the_excerpt( self::$post_id ) );
  }

  /**
   * With the builder paywall on, a post that keeps its own marketing content shows it in excerpts without loading the
   * paywall stylesheet.
   */
  public function test_shows_post_marketing_content_in_excerpts_next_to_the_builder_paywall() {
    $this->use_global_marketing( false );
    update_option( 'memberful_paywall_config', array( 'mode' => 'builder' ) );

    $flag = new ReflectionProperty( 'Memberful_Paywall_Renderer', 'should_print_styles' );
    $flag->setAccessible( true );
    $flag->setValue( null, false );

    $this->assertSame( 'Join now to keep reading. Login to see the full update.', get_the_excerpt( self::$post_id ) );
    $this->assertFalse( $flag->getValue() );
  }

  /**
   * A listing that turns the paywall off outside excerpts keeps it off.
   */
  public function test_leaves_marketing_content_out_when_the_paywall_is_filtered_off() {
    add_filter( 'memberful_wp_should_render_paywall', '__return_false' );

    $this->assertSame( '', $this->render_content() );
  }

  /**
   * Turn global marketing on, as the plugin does at load when the option is set.
   *
   * @param bool $override Whether global marketing content replaces the post's own.
   */
  private function use_global_marketing( bool $override ) {
    update_option( 'memberful_use_global_marketing', 1 );
    update_option( 'memberful_use_global_snippets', 0 );
    update_option( 'memberful_global_marketing_override', $override );

    require_once MEMBERFUL_DIR . '/src/global_marketing.php';
    add_filter( 'memberful_wp_protect_content', 'memberful_get_global_replacement', 1, 1 );
  }
}
