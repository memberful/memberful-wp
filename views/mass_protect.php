<div class="wrap">
	<?php memberful_wp_render('option_tabs', array('active' => 'mass_protect')); ?>
	<?php memberful_wp_render('flash'); ?>
	<div class="update-nag">
		<strong>Be careful:</strong> When you bulk apply these restrict access settings we will <strong>overwrite and replace</strong> any specified individual Post or Page restrict access settings.
	</div>
	<form method="POST" action="<?php echo $form_target ?>">
		<div class="memberful-bulk-apply-box">
			<h3>Bulk apply restrict access settings</h3>
			<fieldset>
				<label>Apply the restrict access settings specified below to:</label>
				<select name="target_for_restriction" id="global-restrict-target" class="postform">
					<option value="all_pages_and_posts" selected="selected">All Pages and Posts</option>
					<option value="all_pages">All Pages</option>
					<option value="all_posts">All Posts</option>
					<option value="all_posts_from_category">All Posts from a category or categories</option>
				</select>
				<ul data-depends-on="global-restrict-target" data-depends-value="all_posts_from_category" class="memberful-global-restrict-access-category-list">
					<?php foreach(get_categories() as $category): ?>
						<li><label><input type="checkbox"  name="memberful_protect_categories[]" value="<?php echo $category->cat_ID ?>"><?php echo $category->cat_name; ?></option></label></li>
			<?php endforeach; ?>
				</ul>
					<p>
						<input type="submit" class="button button-secondary" value="Bulk apply restrict access settings" />
					</p>
			</fieldset>
		</div>
		<div>
		<?php memberful_wp_render( 'metabox', compact( 'subscriptions', 'products', 'marketing_content' ) ); ?>
	</div>
		<?php memberful_wp_nonce_field( 'memberful_options' ); ?>
	</form>
</div>
