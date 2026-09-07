<?php
/**
 * One value chip for a metering token-field.
 *
 * Rendered server-side with a real label/value/name for saved values, and empty inside #memberful-metering-chip-template
 * (metering-admin.js fills label/value/name on clone).
 *
 * @package memberful-wp
 *
 * @var string $label Display label (empty for the template).
 * @var string $value Stored slug/value (empty for the template).
 * @var string $name  Hidden input name, e.g. "…[values][]" (empty for the template).
 */

$label = isset( $label ) ? $label : '';
$value = isset( $value ) ? $value : '';
$name  = isset( $name ) ? $name : '';
?>
<span class="memberful-metering-chip">
  <span class="memberful-metering-chip__label"><?php echo esc_html( $label ); ?></span>
  <button type="button" class="memberful-metering-chip__remove" aria-label="<?php esc_attr_e( 'Remove', 'memberful' ); ?>">
    <?php memberful_wp_render( 'metering/icon-remove', array( 'size' => 12 ) ); ?>
  </button>
  <input type="hidden" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>">
</span>
