<?php
/**
 * Tests for the metering countdown block render in js/src/blocks/metering-countdown/render.php.
 *
 * @package Memberful
 */

/**
 * Class Tests_Metering_Countdown_Block
 */
class Tests_Metering_Countdown_Block extends WP_UnitTestCase {

  /**
   * Metered post used by every test.
   *
   * @var int
   */
  private $post_id;

  /**
   * Create a published post and make it the queried object.
   */
  public function set_up() {
    parent::set_up();
    $this->post_id = self::factory()->post->create( array( 'post_status' => 'publish' ) );
    $this->go_to( get_permalink( $this->post_id ) );
  }

  /**
   * Mark the test post as a metered sample with the given remaining views.
   *
   * @param int $remaining Views left after this one.
   */
  private function allow_sample( int $remaining ) {
    $cache = new ReflectionMethod( 'Memberful_Metering_Access', 'cache' );
    $cache->setAccessible( true );
    $cache->invoke( null, $this->post_id, Memberful_Metering_Access::DECISION_ALLOW_SAMPLE, $remaining );
  }

  /**
   * Render the countdown block with the given attributes.
   *
   * @param array $attributes Block attributes.
   * @return string
   */
  private function render( array $attributes ): string {
    $block = '<!-- wp:memberful/metering-countdown ' . wp_json_encode( $attributes ) . ' /-->';

    return do_blocks( $block );
  }

  /**
   * The remaining count is substituted into the template.
   */
  public function test_renders_the_count_as_plain_text() {
    $this->allow_sample( 3 );

    $html = $this->render( array( 'template' => 'You have {count} free articles left.' ) );

    $this->assertStringContainsString( 'You have 3 free articles left.', $html );
    $this->assertStringContainsString( 'wp-block-memberful-metering-countdown', $html );
  }

  /**
   * HTML in the template is escaped and shown as text, not rendered.
   */
  public function test_escapes_html_in_the_template() {
    $this->allow_sample( 3 );

    $html = $this->render( array( 'template' => '<strong>You have {count} free articles left.</strong>' ) );

    $this->assertStringContainsString( '&lt;strong&gt;You have 3 free articles left.&lt;/strong&gt;', $html );
    $this->assertStringNotContainsString( '<strong', $html );
  }

  /**
   * The singular and last-article messages are chosen by the remaining count.
   */
  public function test_uses_the_singular_and_last_article_messages() {
    $attributes = array(
        'template'            => 'You have {count} free articles left.',
        'singularTemplate'    => 'One more after this.',
        'lastArticleTemplate' => 'Last one.',
    );

    $this->allow_sample( 1 );
    $this->assertStringContainsString( 'One more after this.', $this->render( $attributes ) );

    $this->allow_sample( 0 );
    $this->assertStringContainsString( 'Last one.', $this->render( $attributes ) );
  }

  /**
   * Without a metering decision the block outputs nothing.
   */
  public function test_renders_nothing_when_the_post_is_not_a_metered_sample() {
    $html = $this->render( array( 'template' => 'You have {count} free articles left.' ) );

    $this->assertSame( '', trim( $html ) );
  }
}
