<?php
/**
 * Tests for content protection with the WooCommerce integration in src/contrib/woocommerce.php.
 *
 * @package Memberful
 */

/**
 * Class Tests_WooCommerce_Content_Protection
 */
class Tests_WooCommerce_Content_Protection extends WP_UnitTestCase {

  /**
   * Protected post.
   *
   * @var int
   */
  private static $post_id;

  /**
   * Protected product.
   *
   * @var int
   */
  private static $product_id;

  /**
   * Create a protected post and a protected product.
   *
   * @param WP_UnitTest_Factory $factory Test factory.
   */
  public static function wpSetUpBeforeClass( $factory ) {
    self::$post_id = $factory->post->create(
      array(
        'post_status'  => 'publish',
        'post_content' => '<p>Protected post body.</p>',
      )
    );

    self::$product_id = $factory->post->create(
      array(
        'post_type'    => 'product',
        'post_status'  => 'publish',
        'post_content' => '<p>Product description.</p>',
      )
    );
  }

  /**
   * Load the integration, which the plugin only loads when WooCommerce is active, and view the front page logged out.
   */
  public function set_up() {
    parent::set_up();

    require_once MEMBERFUL_DIR . '/src/contrib/woocommerce.php';
    // Hooks are reset after each test, so add the integration's hooks again.
    new Memberful_Wp_Integration_WooCommerce();

    memberful_wp_set_post_available_to_any_registered_users( self::$post_id, true );
    memberful_wp_set_post_available_to_any_registered_users( self::$product_id, true );

    wp_set_current_user( 0 );
    $this->go_to( home_url( '/' ) );
  }

  /**
   * Render a post's content through the_content, the way a loop does.
   *
   * @param int $post_id Post to render.
   * @return string
   */
  private function render_content( int $post_id ): string {
    $GLOBALS['post'] = get_post( $post_id );
    setup_postdata( $GLOBALS['post'] );

    return apply_filters( 'the_content', get_post_field( 'post_content', $post_id ) );
  }

  /**
   * Product descriptions stay unprotected.
   */
  public function test_leaves_product_content_unprotected() {
    $this->assertStringContainsString( 'Product description.', $this->render_content( self::$product_id ) );
  }

  /**
   * Product content keeps the paywall divider marker for the filters that run after the content filter.
   */
  public function test_keeps_the_paywall_divider_in_product_content() {
    $GLOBALS['post'] = get_post( self::$product_id );
    setup_postdata( $GLOBALS['post'] );

    $content = 'Above. ' . memberful_wp_get_paywall_divider_marker() . ' Below.';

    $this->assertSame( $content, memberful_wp_protect_content( $content ) );
  }

  /**
   * Product content built with Elementor stays protected.
   */
  public function test_keeps_elementor_product_content_protected() {
    add_action( 'elementor/frontend/the_content', 'memberful_wp_protect_content' );

    $GLOBALS['post'] = get_post( self::$product_id );
    setup_postdata( $GLOBALS['post'] );

    $this->assertStringNotContainsString( 'Product description.', apply_filters( 'elementor/frontend/the_content', '<p>Product description.</p>' ) );
  }

  /**
   * Rendering a product leaves posts rendered later in the same request protected.
   */
  public function test_keeps_posts_rendered_after_a_product_protected() {
    $this->render_content( self::$product_id );

    $this->assertStringNotContainsString( 'Protected post body.', $this->render_content( self::$post_id ) );
  }
}
