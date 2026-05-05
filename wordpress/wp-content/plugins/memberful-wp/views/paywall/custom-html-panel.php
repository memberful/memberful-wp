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
	<?php wp_editor( $global_marketing_content, 'memberful_global_marketing_content' ); ?>
</div>