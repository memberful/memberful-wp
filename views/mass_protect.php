<div class="wrap">
	<?php memberful_wp_render('option_tabs', array('active' => 'mass_protect')); ?>
	<?php memberful_wp_render('flash'); ?>
	<p>Set up global content restrictions. These settings will override any individual page restrict access settings.</p>
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
							<select name="default_category" id="default_category" class="postform">
								<option selected="selected">Nothing (disabled)</option>
								<option>Pages and Posts</option>
								<option>Pages</option>
								<option>Posts</option>
								<option>Posts from a category</option>
							</select>
							<ul class="memberful-global-restrict-access-category-list">
								<?php foreach(get_categories() as $category): ?>
									<li><label><input type="checkbox"  name="memberful_protect_categories[]" value="<?php echo $category->cat_ID ?>"><?php echo $category->cat_name; ?></option></label></li>
						<?php endforeach; ?>
							</ul>
						</fieldset>
					</td>
				</tr>
				<tr>
					<th scope="row">Required for access</th>
					<td>
						<fieldset>
							<?php if ( ! empty( $subscriptions ) || ! empty( $products ) ) : ?>
									<?php if ( ! empty( $subscriptions ) ) : ?>
										<ul class="memberful-global-restrict-access-required-list">
										<?php foreach($subscriptions as $id => $subscription): ?>
											<li>
												<label>
													<input type="checkbox" name="memberful_subscription_acl[]" value="<?php echo $id; ?>" <?php checked( $subscription['checked'] ); ?>>
													<?php echo esc_html( $subscription['name'] ); ?>
												</label>
											</li>
										<?php endforeach; ?>
									<?php endif; ?>
									<?php if ( ! empty( $products ) ) : ?>
										<?php foreach($products as $id => $product): ?>
											<li>
												<label>
													<input type="checkbox" name="memberful_product_acl[]" value="<?php echo $id; ?>" <?php checked( $product['checked'] ); ?>>
													<?php echo esc_html( $product['name'] ); ?>
												</label>
											</li>
										<?php endforeach; ?>
										</ul>
									<?php endif; ?>
							<?php else: ?>
									<p><em><?php _e( "We couldn't find any products or subscriptions in your Memberful account. You'll need to add some before you can restrict access.", 'memberful' ); ?></em></p>
							<?php endif; ?>
						</fieldset>
					</td>
				</tr>
				<tr>
					<th scope="row">Marketing content shown to readers without access</th>
					<td>
						<fieldset>
							<div class="memberful-marketing-content">
								<?php

								$editor_id = 'memberful_marketing_content';
								$settings  = array();
								wp_editor( $marketing_content , $editor_id, $settings );

								?>
								<div class="memberful-marketing-content-description">
									<label>
										<input type="checkbox" name="memberful_make_default_marketing_content" value="1">
										Make this the default marketing content for new posts and pages
									</label>
								</div>
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
