<div id="memberful-wrap" class="wrap">
	<div id="memberful-head">
		<h2><?php _e( 'Memberful', 'memberful' ); ?></h2>
	</div>
	<div id="memberful-registered">
		<h1><?php _e( 'Integration Active', 'memberful' ); ?></h1>
		<p><?php printf( __( 'Managing %d products, and %d subscriptions.', 'memberful' ), count( $products ), count( $subscriptions ) ); ?></p>
		<p><?php printf( __( 'Use the <a href="%s">Memberful Admin Panel</a> to manage your members.' ), memberful_url( 'admin' ) ) ?></p>
		<form method="POST" action="<?php echo admin_url('admin.php?page=memberful_options&noheader=true'); ?>">
			<button type="submit" name="manual_sync" class="memberful-button-grey"><?php _e( 'Run Manual Sync', 'memberful' ); ?></button>
			<button type="submit" name="reset_plugin" class="memberful-button-grey"><?php _e( 'Reset plugin', 'memberful' ); ?></button>
		</form>
	</div>
	<div class="memberful-protect-help">
		<?php _e( "To protect individual posts and pages based on a member's products or subscriptions, edit the post or page and look for the Memberful meta box.", 'memberful' ); ?>
	</div>
</div>
