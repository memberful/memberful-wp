<?php
/**
 * Tests for category and tag metering conditions, which store term IDs.
 *
 * @package Memberful
 */

/**
 * Class Tests_Metering_Term_Conditions
 */
class Tests_Metering_Term_Conditions extends WP_UnitTestCase {

  /**
   * Sanitize a single condition row and return the resulting rule groups.
   *
   * @param string $field    Condition field.
   * @param string $operator Condition operator.
   * @param mixed  $values   Raw values.
   * @return array
   */
  private function sanitize_rules( string $field, string $operator, $values ): array {
    $input = array(
      'rules' => array(
        array(
          'match'      => 'all',
          'conditions' => array( compact( 'field', 'operator', 'values' ) ),
        ),
      ),
    );

    return Memberful_Metering_Sanitizer::sanitize( $input, Memberful_Metering_Config::defaults() )['rules'];
  }

  /**
   * Run one condition against a post through the private matcher.
   *
   * @param int    $post_id  Post ID.
   * @param string $field    Condition field.
   * @param string $operator Condition operator.
   * @param array  $values   Saved values.
   * @return bool
   */
  private function condition_matches( int $post_id, string $field, string $operator, array $values ): bool {
    $method = new ReflectionMethod( 'Memberful_Metering_Access', 'condition_matches' );
    $method->setAccessible( true );

    return $method->invoke( null, get_post( $post_id ), compact( 'field', 'operator', 'values' ) );
  }

  /**
   * Term IDs are kept as integers.
   */
  public function test_sanitizer_keeps_term_ids() {
    $category = self::factory()->category->create();
    $tag      = self::factory()->tag->create();

    $category_rules = $this->sanitize_rules( 'category', 'has_any', array( (string) $category ) );
    $tag_rules      = $this->sanitize_rules( 'tag', 'has_any', (string) $tag );

    $this->assertSame( array( $category ), $category_rules[0]['conditions'][0]['values'] );
    $this->assertSame( array( $tag ), $tag_rules[0]['conditions'][0]['values'] );
  }

  /**
   * Zero, non-numeric, negative, and duplicate values are dropped.
   */
  public function test_sanitizer_drops_invalid_term_ids() {
    $category = self::factory()->category->create();

    $rules = $this->sanitize_rules(
      'category',
      'has_any',
      array( (string) $category, (string) $category, '0', 'news', '12abc', '-1', '' )
    );

    $this->assertSame( array( $category ), $rules[0]['conditions'][0]['values'] );
  }

  /**
   * A condition with no valid term IDs left is dropped, along with its now-empty group.
   */
  public function test_sanitizer_drops_condition_with_no_valid_term_ids() {
    $this->assertSame( array(), $this->sanitize_rules( 'category', 'has_any', array( 'Новости' ) ) );
    $this->assertSame( array(), $this->sanitize_rules( 'tag', 'has_none', array( '%d0%bd%d0%be' ) ) );
  }

  /**
   * Deleting a term keeps its condition, so the rule narrows instead of widening to every post in the group.
   */
  public function test_deleted_term_keeps_condition_and_never_matches() {
    $category = self::factory()->category->create();
    $post_id  = self::factory()->post->create( array( 'post_category' => array( $category ) ) );

    wp_delete_term( $category, 'category' );

    $input = array(
      'rules' => array(
        array(
          'match'      => 'all',
          'conditions' => array(
            array(
              'field'    => 'post_type',
              'operator' => 'is_any_of',
              'values'   => array( 'post' ),
            ),
            array(
              'field'    => 'category',
              'operator' => 'has_any',
              'values'   => array( (string) $category ),
            ),
          ),
        ),
      ),
    );
    $rules = Memberful_Metering_Sanitizer::sanitize( $input, Memberful_Metering_Config::defaults() )['rules'];

    $this->assertCount( 2, $rules[0]['conditions'] );
    $this->assertSame( array( $category ), $rules[0]['conditions'][1]['values'] );
    $this->assertFalse( $this->condition_matches( $post_id, 'category', 'has_any', array( $category ) ) );
  }

  /**
   * Category conditions match by term ID, including a non-Latin-named category.
   */
  public function test_category_conditions_match_by_id() {
    $news    = self::factory()->category->create( array( 'name' => 'Новости' ) );
    $other   = self::factory()->category->create( array( 'name' => 'Other' ) );
    $post_id = self::factory()->post->create( array( 'post_category' => array( $news ) ) );

    $this->assertTrue( $this->condition_matches( $post_id, 'category', 'has_any', array( $news ) ) );
    $this->assertFalse( $this->condition_matches( $post_id, 'category', 'has_any', array( $other ) ) );
    $this->assertFalse( $this->condition_matches( $post_id, 'category', 'has_none', array( $news ) ) );
    $this->assertTrue( $this->condition_matches( $post_id, 'category', 'has_none', array( $other ) ) );
  }

  /**
   * Tag conditions match by term ID, and a term's name or slug does not match.
   */
  public function test_tag_conditions_match_by_id() {
    $featured = self::factory()->tag->create( array( 'name' => 'Featured' ) );
    $other    = self::factory()->tag->create( array( 'name' => 'Other' ) );
    $post_id  = self::factory()->post->create();
    wp_set_post_tags( $post_id, array( $featured ) );

    $this->assertTrue( $this->condition_matches( $post_id, 'tag', 'has_any', array( $featured ) ) );
    $this->assertFalse( $this->condition_matches( $post_id, 'tag', 'has_any', array( $other ) ) );
    $this->assertFalse( $this->condition_matches( $post_id, 'tag', 'has_any', array( 'featured' ) ) );
    $this->assertFalse( $this->condition_matches( $post_id, 'tag', 'has_none', array( $featured ) ) );
    $this->assertTrue( $this->condition_matches( $post_id, 'tag', 'has_none', array( $other ) ) );
  }

  /**
   * A saved rule keeps matching after the term's slug changes.
   */
  public function test_rule_survives_slug_change() {
    $category = self::factory()->category->create( array( 'slug' => 'old-slug' ) );
    $post_id  = self::factory()->post->create( array( 'post_category' => array( $category ) ) );

    $values = $this->sanitize_rules( 'category', 'has_any', array( (string) $category ) )[0]['conditions'][0]['values'];

    wp_update_term( $category, 'category', array( 'slug' => 'new-slug' ) );

    $this->assertTrue( $this->condition_matches( $post_id, 'category', 'has_any', $values ) );
    $this->assertFalse( $this->condition_matches( $post_id, 'category', 'has_none', $values ) );
  }
}
