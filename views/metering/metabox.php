<?php
/**
 * Metering "Exempt from meter" metabox body.
 *
 * @package memberful-wp
 *
 * @var bool $exempt Whether this post is currently exempt from metering.
 */
?>
<label>
  <input
    type="checkbox"
    name="memberful_metering_exempt"
    value="1"
    <?php checked( $exempt ); ?>
  >
  <?php esc_html_e( 'Exempt this post from metering', 'memberful' ); ?>
</label>

<p class="description">
  <?php esc_html_e( 'When checked, this post is always fully readable and never counts toward metering limits.', 'memberful' ); ?>
</p>
