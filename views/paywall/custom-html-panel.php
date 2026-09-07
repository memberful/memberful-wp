<?php
/**
 * Paywall custom HTML (legacy WYSIWYG) panel.
 *
 * @package memberful-wp
 *
 * @var string $global_marketing_content
 * @var bool   $is_active
 */

?>
<div class="memberful-paywall-builder__panel" data-panel="custom_html"<?php if ( ! $is_active ) echo ' style="display:none"'; ?>>
  <div id="global_content_required"><?php esc_html_e( 'When using Custom HTML, the marketing content box cannot be empty.', 'memberful' ); ?></div>
  <?php wp_editor( $global_marketing_content, 'memberful_global_marketing_content' ); ?>
</div>
