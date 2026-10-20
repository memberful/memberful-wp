<?php
/**
 * Tests for content protection with the Elementor integration in src/contrib/elementor.php.
 *
 * @package Memberful
 */

/**
 * Class Tests_Elementor_Content_Protection
 */
class Tests_Elementor_Content_Protection extends WP_UnitTestCase {

  /**
   * Protected post built with Elementor.
   *
   * @var int
   */
  private static $post_id;

  /**
   * Unprotected Elementor template, such as a header.
   *
   * @var int
   */
  private static $template_id;

  /**
   * Logged-in user without a plan. Not the logged-out user, whose restricted posts are cached for the whole run.
   *
   * @var int
   */
  private static $user_id;

  /**
   * Create a protected post, an unprotected template, and a user without a plan.
   *
   * @param WP_UnitTest_Factory $factory Test factory.
   */
  public static function wpSetUpBeforeClass( $factory ) {
    self::$post_id     = $factory->post->create( array( 'post_status' => 'publish' ) );
    self::$template_id = $factory->post->create( array( 'post_status' => 'publish' ) );
    self::$user_id     = $factory->user->create( array( 'role' => 'subscriber' ) );
  }

  /**
   * Load the integration, which the plugin only loads when Elementor is active, and protect the post.
   */
  public function set_up() {
    parent::set_up();

    require_once MEMBERFUL_DIR . '/src/contrib/elementor.php';

    memberful_wp_set_post_available_to_anybody_subscribed_to_a_plan( self::$post_id, true );

    wp_set_current_user( self::$user_id );
  }

  /**
   * Render a layout's content the way Elementor's content filter does.
   *
   * @param int $post_id Post the layout belongs to.
   * @return string
   */
  private function render( int $post_id ): string {
    return memberful_wp_protect_elementor_document_content( '<p>Elementor body.</p>', $post_id );
  }

  /**
   * Renders off the main query, such as an add-on's admin-ajax endpoint, are protected.
   */
  public function test_protects_content_rendered_without_a_global_post() {
    $GLOBALS['post'] = null;

    $this->assertStringNotContainsString( 'Elementor body.', $this->render( self::$post_id ) );
    $this->assertNull( $GLOBALS['post'] );
  }

  /**
   * A protected post's layout rendered inside another page is protected.
   */
  public function test_protects_content_rendered_inside_another_post() {
    $this->go_to( get_permalink( self::$template_id ) );
    $GLOBALS['post'] = get_post( self::$template_id );

    $this->assertStringNotContainsString( 'Elementor body.', $this->render( self::$post_id ) );
    $this->assertSame( self::$template_id, $GLOBALS['post']->ID );
  }

  /**
   * The protected post itself shows the paywall.
   */
  public function test_protects_the_queried_post() {
    $this->go_to( get_permalink( self::$post_id ) );
    $GLOBALS['post'] = get_post( self::$post_id );

    $this->assertStringNotContainsString( 'Elementor body.', $this->render( self::$post_id ) );
  }

  /**
   * Unprotected templates, such as a header, render on a protected post's page.
   */
  public function test_leaves_unprotected_templates_on_a_protected_post_alone() {
    $this->go_to( get_permalink( self::$post_id ) );
    $GLOBALS['post'] = get_post( self::$post_id );

    $this->assertStringContainsString( 'Elementor body.', $this->render( self::$template_id ) );
    $this->assertSame( self::$post_id, $GLOBALS['post']->ID );
  }
}
