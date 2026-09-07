<?php
/**
 * Global Marketing settings screen.
 *
 * @package memberful-wp
 *
 * @var bool   $use_global_marketing
 * @var bool   $use_global_snippets
 * @var int    $paragraph_count
 * @var bool   $global_marketing_override
 * @var string $global_marketing_content
 * @var array  $paywall_config
 * @var string $form_target
 */

?>
<div class="wrap">
	<?php memberful_wp_render( 'option_tabs', array( 'active' => 'global_marketing' ) ); ?>
	<?php memberful_wp_render( 'flash' ); ?>

	<form method="POST" action="<?php echo esc_url( $form_target ); ?>">
		<?php memberful_wp_nonce_field( 'memberful_options' ); ?>

		<div class="memberful-bulk-apply-box memberful-bulk-apply-box--wide">
			<h3><?php esc_html_e( 'Global Paywall', 'memberful' ); ?></h3>
			<p>
				<label for="use_global_marketing_checkbox">
					<input id="use_global_marketing_checkbox" class="memberful-label__checkbox--multiline" type="checkbox" name="memberful_use_global_marketing" <?php checked( $use_global_marketing ); ?>>
					<span class="memberful-label__text--multiline">
						<strong><?php esc_html_e( 'Turn on global marketing content', 'memberful' ); ?></strong>
						<?php esc_html_e( ' This setting allows you to create default marketing content to be displayed for all locked posts, pages, categories and tags.', 'memberful' ); ?>
					</span>
				</label>
			</p>

			<div id="global_marketing_options" data-depends-on="use_global_marketing_checkbox" data-depends-value="1"<?php if ( ! $use_global_marketing ) echo ' style="display:none"'; ?>>
				<div id="global_marketing_snippet_options">
					<label for="use_global_snippets_checkbox">
						<input id="use_global_snippets_checkbox" class="memberful-label__checkbox--multiline" type="checkbox" name="memberful_use_global_snippets" <?php checked( $use_global_snippets ); ?>>
						<small class="memberful-label__text--multiline">
							<strong><?php esc_html_e( 'Automatically pull an excerpt from each post.', 'memberful' ); ?></strong>
							<?php esc_html_e( ' Memberful will pull the opening paragraphs from each protected post to use as marketing content for logged out visitors. This feature requires <p> tags in your posts to detect which content to use. If a post contains a Memberful Paywall Divider block, all content above the divider will be shown instead of the excerpt.', 'memberful' ); ?>
						</small>
					</label>

					<div id="global_marketing_paragraph_count_option" data-depends-on="use_global_snippets_checkbox" data-depends-value="1"<?php if ( ! $use_global_snippets ) echo ' style="display:none"'; ?>>
						<p class="memberful-paywall-builder__field">
							<label for="memberful_paragraph_count"><?php esc_html_e( 'Paragraphs before paywall', 'memberful' ); ?></label>
							<input id="memberful_paragraph_count" type="number" min="1" max="10" name="memberful_paragraph_count" value="<?php echo esc_attr( $paragraph_count ); ?>">
						</p>
					</div>
				</div>
				<hr>

				<label for="global_marketing_override_radio_true">
					<input id="global_marketing_override_radio_true" type="radio" name="memberful_global_marketing_override" value="1" <?php checked( $global_marketing_override ); ?>>
					<?php esc_html_e( 'Override all marketing content.', 'memberful' ); ?>
				</label>
				<label for="global_marketing_override_radio_false">
					<input id="global_marketing_override_radio_false" type="radio" name="memberful_global_marketing_override" value="0" <?php checked( ! $global_marketing_override ); ?>>
					<?php esc_html_e( "Only use the global marketing content when other content doesn't exist.", 'memberful' ); ?>
				</label>
			</div>

			<div class="memberful-paywall-builder" data-depends-on="use_global_marketing_checkbox" data-depends-value="1"<?php if ( ! $use_global_marketing ) echo ' style="display:none"'; ?>>
				<?php $paywall_mode = ( isset( $paywall_config['mode'] ) && in_array( $paywall_config['mode'], Memberful_Paywall_Config::MODES, true ) ) ? $paywall_config['mode'] : 'builder'; ?>
				<?php memberful_wp_render( 'paywall/mode-radio', array( 'paywall_config' => $paywall_config ) ); ?>
				<?php memberful_wp_render( 'paywall/builder-panel', array( 'paywall_config' => $paywall_config, 'is_active' => 'builder' === $paywall_mode ) ); ?>
				<?php memberful_wp_render( 'paywall/custom-html-panel', array( 'global_marketing_content' => $global_marketing_content, 'is_active' => 'custom_html' === $paywall_mode ) ); ?>
			</div>
		</div>

		<button type="submit" name="save_global_marketing" class="button button-primary"><?php esc_html_e( 'Save Changes', 'memberful' ); ?></button>
	</form>
</div>