<?php
/**
 * Metering configuration defaults, reads, and writes.
 *
 * @package memberful-wp
 */

/**
 * Class Memberful_Metering_Config.
 */
class Memberful_Metering_Config {
  const OPTION_KEY = 'memberful_metering_config';

  const MATCH_TYPES = array( 'all', 'any' );

  const FIELD_OPERATORS = array(
    'post_type' => array( 'is_any_of', 'is_none_of' ),
    'category'  => array( 'has_any', 'has_none' ),
    'tag'       => array( 'has_any', 'has_none' ),
    'url'       => array( 'contains', 'does_not_contain' ),
  );

  const NEGATIVE_OPERATORS = array( 'is_none_of', 'has_none', 'does_not_contain' );

  /**
   * Canonical default configuration shape.
   *
   * @return array
   */
  public static function defaults(): array {
    return array(
      'enabled'                  => false,
      'period_days'              => 30,
      'anonymous_limit'          => 3,
      'registered_limit'         => 5,
      'apply_to_protected_posts' => false,
      'rules'                    => array(),
      'exclude_rules'            => array(),
    );
  }

  /**
   * Read the stored config merged over defaults.
   *
   * @return array
   */
  public static function get(): array {
    $stored = get_option( self::OPTION_KEY, array() );

    if ( ! is_array( $stored ) ) {
      $stored = array();
    }

    return Memberful_Metering_Sanitizer::sanitize( wp_parse_args( $stored, self::defaults() ), self::defaults() );
  }

  /**
   * Validate, sanitize, and persist a config payload.
   *
   * @param array $input Raw input from the settings form.
   *
   * @return bool True when the option was updated, false when unchanged or on failure.
   */
  public static function save( array $input ): bool {
    $clean = Memberful_Metering_Sanitizer::sanitize( $input, self::defaults() );

    return update_option( self::OPTION_KEY, $clean );
  }

  /**
   * Labels for supported rule fields.
   *
   * @return array
   */
  public static function fields(): array {
    return array(
      'post_type' => __( 'Post type', 'memberful' ),
      'category'  => __( 'Category', 'memberful' ),
      'tag'       => __( 'Tag', 'memberful' ),
      'url'       => __( 'URL', 'memberful' ),
    );
  }

  /**
   * Labels for supported rule operators, grouped by field.
   *
   * @return array
   */
  public static function operators(): array {
    $labels = array(
      'is_any_of'        => __( 'is any of', 'memberful' ),
      'is_none_of'       => __( 'is none of', 'memberful' ),
      'has_any'          => __( 'has any of', 'memberful' ),
      'has_none'         => __( 'has none of', 'memberful' ),
      'contains'         => __( 'contains', 'memberful' ),
      'does_not_contain' => __( 'does not contain', 'memberful' ),
    );

    $operators = array();
    foreach ( self::FIELD_OPERATORS as $field => $field_operators ) {
      foreach ( $field_operators as $operator ) {
        $operators[ $field ][ $operator ] = $labels[ $operator ];
      }
    }

    return $operators;
  }

  /**
   * Public post types available for metering rules.
   *
   * @return array
   */
  public static function public_post_types(): array {
    $post_types = get_post_types( array( 'public' => true ), 'objects' );

    uasort(
      $post_types,
      function( $a, $b ) {
        $a_label = ! empty( $a->label ) ? $a->label : $a->name;
        $b_label = ! empty( $b->label ) ? $b->label : $b->name;

        return strnatcasecmp( $a_label, $b_label );
      }
    );

    return $post_types;
  }
}
