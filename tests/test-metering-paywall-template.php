<?php
/**
 * Tests for the anonymous free-meter render in src/content_filter.php.
 *
 * @package Memberful
 */

/**
 * Class Tests_Metering_Paywall_Template
 */
class Tests_Metering_Paywall_Template extends WP_UnitTestCase {

  /**
   * Metered post used by every test.
   *
   * @var int
   */
  private $post_id;

  /**
   * Create a published post, make it the queried object and mark it as an anonymous free-meter view.
   */
  public function set_up() {
    parent::set_up();
    unset( $GLOBALS['memberful_metering_teaser'] );

    $post = array(
      'post_status'  => 'publish',
      'post_content' => 'A short metered post.',
      'post_excerpt' => '',
    );

    $this->post_id = self::factory()->post->create( $post );
    $this->go_to( get_permalink( $this->post_id ) );
    $this->set_anon_mode( Memberful_Metering_Access::RENDER_FREE_METER );
  }

  /**
   * Reset the anonymous render mode and the held paywall.
   */
  public function tear_down() {
    $this->set_anon_mode( Memberful_Metering_Access::RENDER_NONE );
    unset( $GLOBALS['memberful_metering_teaser'] );
    parent::tear_down();
  }

  /**
   * Set the anonymous render mode for the test post.
   *
   * @param string $mode One of the RENDER_* constants.
   */
  private function set_anon_mode( string $mode ) {
    $anon_render = new ReflectionProperty( 'Memberful_Metering_Access', 'anon_render' );
    $anon_render->setAccessible( true );
    $anon_render->setValue(
      null,
      array(
        'post_id' => (int) $this->post_id,
        'mode'    => $mode,
      )
    );
  }

  /**
   * Render the test post's content through the_content.
   *
   * @return string
   */
  private function render_content(): string {
    $GLOBALS['post'] = get_post( $this->post_id );
    setup_postdata( $GLOBALS['post'] );

    return apply_filters( 'the_content', get_post_field( 'post_content', $this->post_id ) );
  }

  /**
   * Give the test post a teaser paragraph, a paywall divider and a paragraph below it.
   */
  private function use_divider_content() {
    $content = "<!-- wp:paragraph -->\n<p>Teaser paragraph.</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:memberful/paywall-divider /-->\n\n<!-- wp:paragraph -->\n<p>Below the divider.</p>\n<!-- /wp:paragraph -->";

    wp_update_post(
      array(
        'ID'           => $this->post_id,
        'post_content' => $content,
      )
    );
  }

  /**
   * Capture what wp_footer prints for the paywall template.
   *
   * @return string
   */
  private function footer_template(): string {
    ob_start();
    memberful_metering_print_paywall_template();

    return (string) ob_get_clean();
  }

  /**
   * The body sits between the markers and the paywall stays out of the_content.
   */
  public function test_wraps_the_body_in_markers_without_the_paywall() {
    $content = $this->render_content();
    $pattern = '#^<div class="memberful-metering__start" data-memberful-metering="free" hidden></div>.*A short metered post\..*<div class="memberful-metering__end" data-memberful-metering="free" hidden></div>$#s';

    $this->assertMatchesRegularExpression( $pattern, trim( $content ) );
    $this->assertStringNotContainsString( 'memberful-metering__paywall', $content );
    $this->assertStringNotContainsString( memberful_wp_default_paywall_content(), $content );
  }

  /**
   * The excerpt built from a short post carries none of the paywall text.
   */
  public function test_short_post_excerpt_leaves_out_the_paywall() {
    $excerpt = get_the_excerpt( $this->post_id );

    $this->assertSame( 'A short metered post.', trim( $excerpt ) );
  }

  /**
   * The footer prints the paywall once as an inert template, however often the_content ran.
   */
  public function test_prints_the_paywall_once_as_a_template() {
    $this->render_content();
    $this->render_content();

    $footer = $this->footer_template();

    $this->assertSame( 1, substr_count( $footer, '<template id="memberful-metering-paywall">' ) );
    $this->assertStringContainsString( '<aside class="memberful-metering__paywall" data-memberful-metering="free">', $footer );
  }

  /**
   * The paywall is built in wp_footer, not while the_content runs, so integrations never see the post as paywalled.
   */
  public function test_builds_the_paywall_outside_the_content() {
    $builds = 0;
    $count  = function ( $content ) use ( &$builds ) {
      $builds++;
      return $content;
    };
    add_filter( 'memberful_wp_protect_content', $count );

    $this->render_content();
    $this->assertSame( 0, $builds );

    $this->footer_template();
    $this->assertSame( 1, $builds );

    remove_filter( 'memberful_wp_protect_content', $count );
  }

  /**
   * The template carries the teaser above a paywall divider.
   */
  public function test_template_includes_the_divider_teaser() {
    $this->use_divider_content();

    $content = $this->render_content();
    $footer  = $this->footer_template();

    $this->assertStringContainsString( 'Below the divider.', $content );
    $this->assertStringNotContainsString( memberful_wp_get_paywall_divider_marker(), $content );
    $this->assertStringContainsString( 'Teaser paragraph.', $footer );
    $this->assertStringNotContainsString( 'Below the divider.', $footer );
  }

  /**
   * An excerpt rendered first (blocks stripped, so no divider) does not keep the teaser out of the template.
   */
  public function test_template_keeps_the_divider_teaser_after_an_excerpt_render() {
    $this->use_divider_content();

    get_the_excerpt( $this->post_id );
    $this->render_content();

    $this->assertStringContainsString( 'Teaser paragraph.', $this->footer_template() );
  }

  /**
   * Nothing is wrapped or printed when the post is not an anonymous free-meter view.
   */
  public function test_leaves_unmetered_posts_alone() {
    $this->set_anon_mode( Memberful_Metering_Access::RENDER_NONE );

    $content = $this->render_content();

    $this->assertStringNotContainsString( 'memberful-metering__start', $content );
    $this->assertSame( '', $this->footer_template() );
  }
}
