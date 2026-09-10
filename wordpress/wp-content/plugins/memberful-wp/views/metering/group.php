<?php
/**
 * One metering rule group: match selector + condition rows.
 *
 * Rendered with a real index for saved groups, and with the __GROUP__
 * placeholder inside the JS group <template> scaffolds.
 *
 * @package memberful-wp
 *
 * @var string $scope             'rules', 'exclude_rules', or a placeholder.
 * @var mixed  $group_index       Group index (int) or a placeholder string.
 * @var string $match             'all' or 'any'.
 * @var string $intro             Sentence start, e.g. 'Meter content when'.
 * @var array  $conditions        List of saved condition arrays (empty for templates).
 * @var array  $fields            Field key => label.
 * @var array  $operators         Field key => [ operator key => label ].
 * @var array  $post_type_options Post type slug => label.
 */

$conditions  = is_array( $conditions ) ? array_values( $conditions ) : array();
$render_vars = compact( 'fields', 'operators', 'post_type_options' );
?>
<div class="memberful-metering-rule-group" data-scope="<?php echo esc_attr( $scope ); ?>" data-group-index="<?php echo esc_attr( $group_index ); ?>" data-next-condition-index="<?php echo esc_attr( count( $conditions ) ); ?>">
  <div class="memberful-metering-rule-group__header">
    <p class="memberful-metering-rule-group__intro">
      <?php echo esc_html( $intro ); ?>
      <select class="memberful-metering-rule-group__match" name="memberful_metering[<?php echo esc_attr( $scope ); ?>][<?php echo esc_attr( $group_index ); ?>][match]" aria-label="<?php esc_attr_e( 'Match', 'memberful' ); ?>">
        <option value="all" <?php selected( 'all', $match ); ?>><?php esc_html_e( 'all', 'memberful' ); ?></option>
        <option value="any" <?php selected( 'any', $match ); ?>><?php esc_html_e( 'any', 'memberful' ); ?></option>
      </select>
      <?php esc_html_e( 'of these are true:', 'memberful' ); ?>
    </p>
    <button type="button" class="memberful-metering-remove-group" aria-label="<?php esc_attr_e( 'Remove group', 'memberful' ); ?>">
      <?php memberful_wp_render( 'metering/icon-remove', array( 'size' => 16 ) ); ?>
    </button>
  </div>

  <div class="memberful-metering-conditions">
    <?php foreach ( $conditions as $condition_index => $condition ) : ?>
      <?php
      memberful_wp_render(
        'metering/condition',
        array_merge(
          $render_vars,
          array(
            'scope'           => $scope,
            'group_index'     => $group_index,
            'condition_index' => $condition_index,
            'condition'       => $condition,
          )
        )
      );
      ?>
    <?php endforeach; ?>
  </div>

  <div class="memberful-metering-rule-group__footer">
    <button type="button" class="memberful-metering-add-condition">+ <?php esc_html_e( 'Add condition', 'memberful' ); ?></button>
  </div>
</div>
