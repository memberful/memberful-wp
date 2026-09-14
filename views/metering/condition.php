<?php
/**
 * One metering condition row: field / operator / value(s).
 *
 * Rendered with real indices for saved rows, and with placeholder tokens
 * (__SCOPE__ / __GROUP__ / __COND__) inside the JS <template> scaffolds.
 *
 * @package memberful-wp
 *
 * @var string $scope             Rule scope: 'rules', 'exclude_rules', or a placeholder.
 * @var mixed  $group_index       Group index (int) or a placeholder string.
 * @var mixed  $condition_index   Condition index (int) or a placeholder string.
 * @var array  $condition         Saved condition: field, operator, values.
 * @var array  $fields            Field key => label.
 * @var array  $operators         Field key => [ operator key => label ].
 * @var array  $post_type_options Post type slug => label.
 */

$field           = isset( $condition['field'] ) && isset( $fields[ $condition['field'] ] ) ? $condition['field'] : 'post_type';
$field_operators = isset( $operators[ $field ] ) ? $operators[ $field ] : array();
$operator        = isset( $condition['operator'] ) && isset( $field_operators[ $condition['operator'] ] ) ? $condition['operator'] : (string) key( $field_operators );
$values          = isset( $condition['values'] ) && is_array( $condition['values'] ) ? $condition['values'] : array();
$name_prefix     = 'memberful_metering[' . $scope . '][' . $group_index . '][conditions][' . $condition_index . ']';
?>
<div class="memberful-metering-condition" data-field="<?php echo esc_attr( $field ); ?>">
  <select class="memberful-metering-condition-field" name="<?php echo esc_attr( $name_prefix ); ?>[field]" aria-label="<?php esc_attr_e( 'Field', 'memberful' ); ?>">
    <?php foreach ( $fields as $field_key => $field_label ) : ?>
      <option value="<?php echo esc_attr( $field_key ); ?>" <?php selected( $field_key, $field ); ?>><?php echo esc_html( $field_label ); ?></option>
    <?php endforeach; ?>
  </select>

  <select class="memberful-metering-condition-operator" name="<?php echo esc_attr( $name_prefix ); ?>[operator]" aria-label="<?php esc_attr_e( 'Operator', 'memberful' ); ?>">
    <?php foreach ( $field_operators as $operator_key => $operator_label ) : ?>
      <option value="<?php echo esc_attr( $operator_key ); ?>" <?php selected( $operator_key, $operator ); ?>><?php echo esc_html( $operator_label ); ?></option>
    <?php endforeach; ?>
  </select>

  <div class="memberful-metering-condition-values">
    <?php if ( 'url' === $field ) : ?>
      <input type="text" class="regular-text memberful-metering-condition-text" name="<?php echo esc_attr( $name_prefix ); ?>[values]" value="<?php echo esc_attr( implode( ', ', $values ) ); ?>" placeholder="<?php esc_attr_e( '/path-fragment', 'memberful' ); ?>">
    <?php else : ?>
      <?php $taxonomy = 'tag' === $field ? 'post_tag' : ( 'category' === $field ? 'category' : '' ); ?>
      <div class="memberful-metering-tokenfield" data-field="<?php echo esc_attr( $field ); ?>" data-name="<?php echo esc_attr( $name_prefix ); ?>[values]">
        <?php
        foreach ( $values as $value ) :
          if ( 'post_type' === $field ) {
            $label = isset( $post_type_options[ $value ] ) ? $post_type_options[ $value ] : $value;
          } else {
            $term  = $taxonomy ? get_term_by( 'slug', $value, $taxonomy ) : false;
            $label = ( $term && ! is_wp_error( $term ) ) ? $term->name : $value;
          }
          memberful_wp_render(
            'metering/chip',
            array(
              'label' => $label,
              'value' => $value,
              'name'  => $name_prefix . '[values][]',
            )
          );
        endforeach;
        ?>
        <input type="text" class="memberful-metering-tokenfield__input" autocomplete="off" placeholder="<?php esc_attr_e( 'Search…', 'memberful' ); ?>" aria-label="<?php esc_attr_e( 'Search', 'memberful' ); ?>">
        <ul class="memberful-metering-tokenfield__list" role="listbox" hidden></ul>
      </div>
    <?php endif; ?>
  </div>

  <button type="button" class="memberful-metering-remove-condition" aria-label="<?php esc_attr_e( 'Remove condition', 'memberful' ); ?>">
    <?php memberful_wp_render( 'metering/icon-remove', array( 'size' => 16 ) ); ?>
  </button>
</div>
