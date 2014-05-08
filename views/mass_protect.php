<div class="wrap">
	<?php memberful_wp_render('option_tabs', array('active' => 'mass_protect')); ?>
	<?php memberful_wp_render('flash'); ?>
	<form method="POST" action="<?php echo $form_target ?>">
		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row">Globally restrict access to</th>
					<td>
						<fieldset>
							<legend class="screen-reader-text">
								<span>Globally restrict access to</span>
							</legend>
							<p>
							<label>
								<input type="radio" class="tog" checked="checked">
								Nothing
							</label>
							</p>
							<p>
							<label>
								<input type="radio" class="tog">
								Pages and Posts
							</label>
							</p>
							<p>
							<label>
								<input type="radio" class="tog">
								Pages
							</label>
							</p>
							<p>
							<label>
								<input type="radio" class="tog">
								Posts
							</label>
							</p>
							<p>
							<label>
								<input type="radio" class="tog">
								Specific Posts from a category
							</label>
							</p>
							<ul class="memberful-global-restrict-access-category-list">
								<?php foreach(get_categories() as $category): ?>
									<li><label><input type="checkbox"  name="memberful_protect_categories[]" value="<?php echo $category->cat_ID ?>"><?php echo $category->cat_name; ?></option></label></li>
						<?php endforeach; ?>
							</ul>
						</fieldset>
					</td>
				</tr>
				<tr>
					<th scope="row">Required for access and marketing content</th>
					<td>
						<fieldset>
							<?php memberful_wp_render( 'metabox', compact( 'subscriptions', 'products', 'marketing_content' ) ); ?>
						</fieldset>
					</td>
				</tr>
			</tbody>
		</table>
	<fieldset>
		<input type="submit" class="button button-primary" value="Save Changes" />
	</fieldset>
	<?php memberful_wp_nonce_field( 'memberful_options' ); ?>
	</form>
</div>
