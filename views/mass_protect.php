<div class="wrap">
	<?php memberful_wp_render('option_tabs', array('active' => 'mass_protect')); ?>
	<?php memberful_wp_render('flash'); ?>
	<form method="POST" action="<?php echo $form_target ?>">
		<fieldset>
			<?php memberful_wp_render( 'metabox', compact( 'subscriptions', 'products', 'marketing_content' ) ); ?>
		</fieldset>
		<fieldset>
			<label><input type="checkbox" name="memberful_protect_all_pages" value="1" />Protect all pages</label>
		</fieldset>
		<fieldset>
			Protect posts in these categories:
			<div>
			<?php foreach(get_categories() as $category): ?>
				<label><input type="checkbox" name="memberful_protect_categories[]" value="<?php echo $category->cat_ID ?>"><?php echo $category->cat_name; ?></option></label>
			<?php endforeach; ?>
			</div>
		</fieldset>
		<fieldset>
			<input type="submit" value="Apply protection" />
		</fieldset>
		<?php memberful_wp_nonce_field( 'memberful_options' ); ?>
	</form>
</div>
