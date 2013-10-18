<?php memberful_wp_render('flash'); ?>
<div id="memberful-wrap" class="wrap">
	<div id="memberful-head">
		<h2><?php _e( 'Memberful', 'memberful' ); ?></h2>
	</div>
	<div id="memberful-registered">
		<h1><?php _e( 'Integration Active', 'memberful' ); ?></h1>
		<h2><?php printf( __( 'Syncing %d products and %d subscriptions.', 'memberful' ), count( $products ), count( $subscriptions ) ); ?></h2>
		<p><?php printf( __( '<a href="%s">Sign in to your Memberful account</a> to manage products, subscriptions, members, and orders.' ), memberful_url( 'admin' ) ) ?></p>
		<form method="POST" action="<?php echo admin_url('admin.php?page=memberful_options&noheader=true'); ?>">
			<?php memberful_wp_nonce_field( 'memberful_setup' ); ?>
			<button type="submit" name="manual_sync" class="button action"><?php _e( 'Run manual sync', 'memberful' ); ?></button>
			<button type="submit" name="reset_plugin" class="button memberful-red-button action"><?php _e( 'Disconnect', 'memberful' ); ?></button>
		</form>
	</div>
	<div class="memberful-protect-help">
		<?php _e( "To protect individual posts and pages, edit the post or page and look for the Memberful meta box below the editor.", 'memberful' ); ?>
	</div>
</div>
