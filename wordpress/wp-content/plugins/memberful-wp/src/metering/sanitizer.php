<?php
/**
 * Sanitization for metering configuration.
 *
 * @package memberful-wp
 */

/**
 * Class Memberful_Metering_Sanitizer.
 */
class Memberful_Metering_Sanitizer {
  /**
   * Sanitize a raw metering-config input array against defaults.
   *
   * @param array $input    Raw input from the settings form.
   * @param array $defaults Canonical defaults from Memberful_Metering_Config::defaults().
   *
   * @return array Sanitized config ready for update_option().
   */
  public static function sanitize( array $input, array $defaults ): array {
    $input = array_intersect_key( $input, $defaults );
    $clean = $defaults;

    $clean['enabled']                  = ! empty( $input['enabled'] );
    $clean['apply_to_protected_posts'] = ! empty( $input['apply_to_protected_posts'] );

    $period               = absint( $input['period_days'] ?? 0 );
    $clean['period_days'] = $period > 0 ? $period : $defaults['period_days'];

    $clean['anonymous_limit']  = self::limit( $input['anonymous_limit'] ?? '', $defaults['anonymous_limit'] );
    $clean['registered_limit'] = self::limit( $input['registered_limit'] ?? '', $defaults['registered_limit'] );

    $clean['rules']         = self::rules( $input['rules'] ?? array() );
    $clean['exclude_rules'] = self::rules( $input['exclude_rules'] ?? array() );

    return $clean;
  }

  /**
   * Sanitize a free-views limit. A blank field keeps the default; an explicit 0 is a valid "no free views" setting.
   *
   * @param mixed $raw     Raw field value.
   * @param int   $default Default limit.
   *
   * @return int
   */
  private static function limit( $raw, int $default ): int {
    $raw   = trim( (string) $raw );
    $limit = '' === $raw ? $default : absint( $raw );

    return min( $limit, Memberful_Metering_Storage::MAX_VIEWS );
  }

  /**
   * Sanitize all rule groups, dropping any that end up with no valid conditions.
   *
   * @param mixed $rules Raw rule groups.
   *
   * @return array
   */
  private static function rules( $rules ): array {
    if ( ! is_array( $rules ) ) {
      return array();
    }

    $clean = array();

    foreach ( $rules as $group ) {
      if ( ! is_array( $group ) ) {
        continue;
      }

      $match      = isset( $group['match'] ) ? sanitize_key( $group['match'] ) : 'all';
      $match      = in_array( $match, Memberful_Metering_Config::MATCH_TYPES, true ) ? $match : 'all';
      $conditions = self::conditions( $group['conditions'] ?? array() );

      if ( ! empty( $conditions ) ) {
        $clean[] = array(
          'match'      => $match,
          'conditions' => $conditions,
        );
      }
    }

    return $clean;
  }

  /**
   * Sanitize condition rows for one rule group, dropping rows with invalid field/operator/values.
   *
   * @param mixed $conditions Raw condition rows.
   *
   * @return array
   */
  private static function conditions( $conditions ): array {
    if ( ! is_array( $conditions ) ) {
      return array();
    }

    $clean = array();

    foreach ( $conditions as $row ) {
      if ( ! is_array( $row ) ) {
        continue;
      }

      $field = isset( $row['field'] ) ? sanitize_key( $row['field'] ) : '';
      if ( ! array_key_exists( $field, Memberful_Metering_Config::FIELD_OPERATORS ) ) {
        continue;
      }

      $operator = isset( $row['operator'] ) ? sanitize_key( $row['operator'] ) : '';
      if ( ! in_array( $operator, Memberful_Metering_Config::FIELD_OPERATORS[ $field ], true ) ) {
        continue;
      }

      $values = self::values( $field, $row['values'] ?? array() );
      if ( empty( $values ) ) {
        continue;
      }

      $clean[] = compact( 'field', 'operator', 'values' );
    }

    return $clean;
  }

  /**
   * Sanitize condition values for a given field. Accepts arrays or comma-separated strings.
   *
   * @param string $field  Condition field.
   * @param mixed  $values Raw values.
   *
   * @return array
   */
  private static function values( string $field, $values ): array {
    $values = is_array( $values ) ? $values : preg_split( '/\s*,\s*/', (string) $values );
    $values = array_filter( array_map( 'trim', array_map( 'strval', (array) $values ) ), 'strlen' );

    switch ( $field ) {
      case 'post_type':
        $values = array_intersect(
          array_map( 'sanitize_key', $values ),
          get_post_types( array( 'public' => true ), 'names' )
        );
        break;
      case 'category':
      case 'tag':
        $values = array_map( 'strtolower', array_map( 'sanitize_text_field', $values ) );
        break;
      case 'url':
        $values = array_map( 'sanitize_text_field', $values );
        break;
      default:
        return array();
    }

    return array_values( array_unique( $values ) );
  }
}
