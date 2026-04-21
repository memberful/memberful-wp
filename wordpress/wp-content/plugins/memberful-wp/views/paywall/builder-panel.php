<?php
/**
 * Paywall builder structured-fields panel.
 *
 * @package memberful-wp
 *
 * @var array $paywall_config
 */

$layout_labels = array(
	'simple' => __( 'Simple', 'memberful' ),
	'card'   => __( 'Card', 'memberful' ),
	'banner' => __( 'Banner', 'memberful' ),
);

$button_shape_labels = array(
	'pill'    => __( 'Pill', 'memberful' ),
	'rounded' => __( 'Rounded', 'memberful' ),
	'square'  => __( 'Square', 'memberful' ),
);

$features_textarea = implode( "\n", (array) $paywall_config['features'] );
?>
<div class="memberful-paywall-builder__panel" data-panel="builder">
	<fieldset class="memberful-paywall-builder__layout">
		<legend class="memberful-paywall-builder__legend"><?php esc_html_e( 'Layout', 'memberful' ); ?></legend>
		<?php foreach ( Memberful_Paywall_Config::LAYOUTS as $layout_key ) : ?>
			<label>
				<input type="radio" name="memberful_paywall[layout]" value="<?php echo esc_attr( $layout_key ); ?>" <?php checked( $layout_key, $paywall_config['layout'] ); ?>>
				<?php echo esc_html( $layout_labels[ $layout_key ] ?? $layout_key ); ?>
			</label>
		<?php endforeach; ?>
	</fieldset>

	<table class="form-table memberful-paywall-builder__fields" role="presentation">
		<tbody>
			<tr>
				<th scope="row"><label for="memberful-paywall-heading"><?php esc_html_e( 'Heading', 'memberful' ); ?></label></th>
				<td>
					<input id="memberful-paywall-heading" type="text" class="regular-text" name="memberful_paywall[heading]" value="<?php echo esc_attr( $paywall_config['heading'] ); ?>">
					<label for="memberful-paywall-heading-tag" class="screen-reader-text"><?php esc_html_e( 'Heading style', 'memberful' ); ?></label>
					<select id="memberful-paywall-heading-tag" name="memberful_paywall[heading_tag]">
						<?php foreach ( Memberful_Paywall_Config::HEADING_TAGS as $heading_tag_option ) : ?>
							<option value="<?php echo esc_attr( $heading_tag_option ); ?>" <?php selected( $heading_tag_option, $paywall_config['heading_tag'] ); ?>><?php echo esc_html( strtoupper( $heading_tag_option ) ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="memberful-paywall-subheading"><?php esc_html_e( 'Subheading', 'memberful' ); ?></label></th>
				<td>
					<input id="memberful-paywall-subheading" type="text" class="regular-text" name="memberful_paywall[subheading]" value="<?php echo esc_attr( $paywall_config['subheading'] ); ?>">
					<label for="memberful-paywall-subheading-tag" class="screen-reader-text"><?php esc_html_e( 'Subheading style', 'memberful' ); ?></label>
					<select id="memberful-paywall-subheading-tag" name="memberful_paywall[subheading_tag]">
						<?php foreach ( Memberful_Paywall_Config::SUBHEADING_TAGS as $subheading_tag_option ) : ?>
							<option value="<?php echo esc_attr( $subheading_tag_option ); ?>" <?php selected( $subheading_tag_option, $paywall_config['subheading_tag'] ); ?>><?php echo esc_html( strtoupper( $subheading_tag_option ) ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="memberful-paywall-features"><?php esc_html_e( 'Features', 'memberful' ); ?></label></th>
				<td>
					<textarea id="memberful-paywall-features" class="large-text code" rows="4" name="memberful_paywall[features]"><?php echo esc_textarea( $features_textarea ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Enter one feature per line. Each line renders as a check-marked list item in the paywall.', 'memberful' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="memberful-paywall-button-label"><?php esc_html_e( 'Button label', 'memberful' ); ?></label></th>
				<td>
					<input id="memberful-paywall-button-label" type="text" class="regular-text" name="memberful_paywall[button_label]" value="<?php echo esc_attr( $paywall_config['button_label'] ); ?>">
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="memberful-paywall-subscribe-url"><?php esc_html_e( 'Subscribe URL', 'memberful' ); ?></label></th>
				<td>
					<input id="memberful-paywall-subscribe-url" type="url" class="regular-text" name="memberful_paywall[subscribe_url]" value="<?php echo esc_attr( $paywall_config['subscribe_url'] ); ?>" placeholder="<?php echo esc_attr( memberful_registration_page_url() ); ?>">
					<p class="description"><?php esc_html_e( 'Leave blank to use your Memberful registration page.', 'memberful' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="memberful-paywall-signin-url"><?php esc_html_e( 'Sign-in URL', 'memberful' ); ?></label></th>
				<td>
					<input id="memberful-paywall-signin-url" type="url" class="regular-text" name="memberful_paywall[sign_in_url]" value="<?php echo esc_attr( $paywall_config['sign_in_url'] ); ?>" placeholder="<?php echo esc_attr( memberful_sign_in_url() ); ?>">
					<p class="description"><?php esc_html_e( 'Leave blank to use your Memberful sign-in link.', 'memberful' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="memberful-paywall-brand-color"><?php esc_html_e( 'Brand colour', 'memberful' ); ?></label></th>
				<td>
					<input id="memberful-paywall-brand-color" type="text" class="memberful-paywall-builder__color" name="memberful_paywall[brand_color]" value="<?php echo esc_attr( $paywall_config['brand_color'] ); ?>" data-default-color="<?php echo esc_attr( $paywall_config['brand_color'] ); ?>">
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="memberful-paywall-button-shape"><?php esc_html_e( 'Button shape', 'memberful' ); ?></label></th>
				<td>
					<select id="memberful-paywall-button-shape" name="memberful_paywall[button_shape]">
						<?php foreach ( Memberful_Paywall_Config::BUTTON_SHAPES as $shape_key ) : ?>
							<option value="<?php echo esc_attr( $shape_key ); ?>" <?php selected( $shape_key, $paywall_config['button_shape'] ); ?>><?php echo esc_html( $button_shape_labels[ $shape_key ] ?? $shape_key ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="memberful-paywall-custom-css"><?php esc_html_e( 'Custom CSS', 'memberful' ); ?></label></th>
				<td>
					<textarea id="memberful-paywall-custom-css" class="large-text code" rows="6" name="memberful_paywall[custom_css]"><?php echo esc_textarea( $paywall_config['custom_css'] ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Rules are scoped under .memberful-paywall when saved.', 'memberful' ); ?></p>
				</td>
			</tr>
		</tbody>
	</table>
</div>
