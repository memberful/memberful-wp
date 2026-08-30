<?php
/**
 * Paywall builder mode radio.
 *
 * @package memberful-wp
 *
 * @var array $paywall_config
 */

?>
<fieldset class="memberful-paywall-builder__mode">
  <legend class="screen-reader-text"><?php esc_html_e( 'Content source', 'memberful' ); ?></legend>
  <div class="memberful-paywall-builder__mode-tabs">
    <label class="memberful-paywall-builder__mode-tab">
      <input type="radio" name="memberful_paywall[mode]" value="builder" <?php checked( 'builder', $paywall_config['mode'] ); ?>>
      <span><?php esc_html_e( 'Visual Paywall', 'memberful' ); ?></span>
    </label>
    <label class="memberful-paywall-builder__mode-tab">
      <input type="radio" name="memberful_paywall[mode]" value="custom_html" <?php checked( 'custom_html', $paywall_config['mode'] ); ?>>
      <span><?php esc_html_e( 'Custom HTML', 'memberful' ); ?></span>
    </label>
  </div>
</fieldset>
