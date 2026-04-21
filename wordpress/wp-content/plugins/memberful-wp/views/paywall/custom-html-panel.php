<?php
/**
 * Paywall custom HTML (legacy WYSIWYG) panel.
 *
 * @package memberful-wp
 *
 * @var string $global_marketing_content
 */

?>
<div class="memberful-paywall-builder__panel" data-panel="custom_html">
	<?php wp_editor( $global_marketing_content, 'memberful_global_marketing_content' ); ?>
</div>