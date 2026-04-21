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
	<legend class="memberful-paywall-builder__legend"><?php esc_html_e( 'Content source', 'memberful' ); ?></legend>
	<label>
		<input type="radio" name="memberful_paywall[mode]" value="builder" <?php checked( 'builder', $paywall_config['mode'] ); ?>>
		<?php esc_html_e( 'Builder', 'memberful' ); ?>
	</label>
	<label>
		<input type="radio" name="memberful_paywall[mode]" value="custom_html" <?php checked( 'custom_html', $paywall_config['mode'] ); ?>>
		<?php esc_html_e( 'Custom HTML (advanced)', 'memberful' ); ?>
	</label>
</fieldset>
