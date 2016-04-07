<div class="wrap">
	<?php memberful_wp_render('option_tabs', array('active' => 'settings')); ?>
	<?php memberful_wp_render('flash'); ?>
	<div id="memberful-wrap">
		<div id="memberful-registered" class="postbox">
			<h1><?php _e( 'Integration Active', 'memberful' ); ?></h1>
			<h2><?php printf( __( 'Syncing %d plans and %d downloads.', 'memberful' ), count( $subscriptions ), count( $products ) ); ?></h2>
			<p><?php printf( __( '<a href="%s">Sign in to your Memberful account</a> to manage products, subscriptions, members, and orders.' ), memberful_url( 'admin' ) ) ?></p>
			<form method="POST" action="<?php echo memberful_wp_plugin_settings_url(TRUE) ?>">
				<?php memberful_wp_nonce_field( 'memberful_options' ); ?>
				<button type="submit" name="manual_sync" class="button action"><?php _e( 'Run manual sync', 'memberful' ); ?></button>
				<button type="submit" name="reset_plugin" class="memberful-red-button"><?php _e( 'Disconnect', 'memberful' ); ?></button>
			</form>
		</div>
		<div class="memberful-protect-help postbox">
			<?php _e( "To protect content, edit a post or page and look for the <em>Memberful: Restrict Access</em> box.", 'memberful' ); ?>
		</div>
	</div>
	<form method="POST" action="<?php echo memberful_wp_plugin_settings_url(TRUE) ?>">
		<?php memberful_wp_nonce_field( 'memberful_options' ); ?>
		<p>
			<label for="extended_login_period_checkbox">
				<input id="extended_login_period_checkbox" type="checkbox" name="extend_auth_cookie_expiration" <?php if( $extend_auth_cookie_expiration ): ?>checked="checked"<?php endif; ?>>
				Keep all WordPress users logged in for 1 year (Memberful default).
			</label>
		</p>
		<button type="submit" name="save_changes" class="button button-primary">Save Changes</button>
	</form>
</div>
