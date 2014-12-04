<div class="wrap">
	<?php memberful_wp_render('option_tabs', array('active' => 'protect_bbpress')); ?>
	<?php memberful_wp_render('flash'); ?>
	<form method="POST" action="<?php echo $form_target ?>">
		<?php memberful_wp_nonce_field( 'memberful_options' ); ?>
		<div class="memberful-bbpress-enable">
			<label>
				<input type="hidden" name="memberful_protect_bbpress" value="0" />
				<input type="checkbox" name="memberful_protect_bbpress" value="1" <?php checked($protect_bbpress) ?>/>
				Protect ALL bbPress forums with Memberful
			</label>
		</div>
		<div class="clear">
			<input type="submit" class="button button-secondary" value="<?php _e( "Apply settings", 'memberful' ); ?>" />
		</div>
		<div class="memberful-restrict-access-options memberful-bbpress-acl" data-depends-on="memberful_protect_bbpress" data-depends-value="1">
			<h4>Who can access forums?</h4>
<?php
memberful_wp_render(
	'acl_selection',
	array(
		'subscriptions' => $plans,
		'products' => $downloads,
		'viewable_by_any_registered_users' => $restricted_to_registered_users
	)
)
?>
		</div>
		<div class="memberful-bbpress-redirect" data-depends-on="memberful_protect_bbpress" data-depends-value="1">
			<h4>Where should users who aren't allowed to access forums be sent?</h4>
			<div>
				<fieldset>
					<div>
						<label>	
							<input type="radio" name="memberful_send_unauthorized_users" value="homepage" <?php checked($send_unauthorized_users_to_homepage); ?> />
							Send them to the homepage
						</label>
					</div>
					<div>
						<label>
							<input type="radio" name="memberful_send_unauthorized_users" value="url" <?php checked(!$send_unauthorized_users_to_homepage); ?> />
							Send them to a specific URL
						</label>
					</div>
					<div data-depends-on="memberful_send_unauthorized_users" data-depends-value="url">
						<label for="memberful_send_unauthorized_users_to_url">Send them to this URL:</label>
						<input type="text" id="memberful_send_unauthorized_users_to_url" name="memberful_send_unauthorized_users_to_url" value="<?php echo esc_attr($send_unauthorized_users_to_url); ?>" placeholder="http://..." />
					</div>
				</fieldset>
			</div>
		</div>
	</form>
</div>
