<div class="wrap">
	<?php memberful_wp_render('option_tabs', array('active' => 'private_user_feed_settings')); ?>
	<?php memberful_wp_render('flash'); ?>
	<div id="memberful-wrap">
		<form method="POST" action="<?php echo $form_target ?>">
			<div class="memberful-private-rss-feed-settings-box">
				<div class="postbox plans-for-rss">
					<fieldset>
						<?php if ( ! empty( $subscription_plans ) ) : ?>
							<div id="memberful-private-user-feed-subscription-list">
								<h3><?php _e( 'Enable private user RSS feeds', 'memberful' ); ?></h3>
								<p><?php _e( "Provide a private user RSS feed of <strong>ALL POSTS</strong> for active subscribers to these Subscription Plans:", 'memberful' ); ?></p>
								<ul>
									<?php foreach($subscription_plans as $id => $subscription): ?>
										<li>
											<label>
												<input type="checkbox"
															 name="memberful_private_feed_subscriptions[]"
															 value="<?php echo $id; ?>"
															<?php checked(in_array($id, $current_feed_subscriptions));?>
														>
												<?php echo esc_html( $subscription['name'] ); ?>
											</label>
										</li>
									<?php endforeach; ?>
								</ul>
							</div>
						<?php else : ?>
							<p class="memberful-private-feed-error"><?php _e( "There are no available Subscription Plans.", 'memberful' ); ?></p>
						<?php endif; ?>
						<p>
							<input type="submit" name="memberful_private_feed_subscriptions_submit" class="button button-primary" value="<?php _e( "Save Changes", 'memberful' ); ?>" />
						</p>
					</fieldset>
				</div>
				<div class="memberful-private-feed-instructions-container memberful-protect-help postbox">
					<?php _e( '<strong>To share the link with active members</strong> add the <em>[memberful_private_user_feed_link]</em> shortcode in a page or use the <em> memberful_private_rss_feed_link()</em> function in your WordPress theme.</code>', 'memberful' ); ?> <strong></strong>
				</div>
			</div>
			<?php memberful_wp_nonce_field( 'memberful_options' ); ?>
		</form>
	</div>
</div>
