<div id="memberful-wrap" class="wrap">
	<div id="memberful-head">
		<h2>Memberful</h2>
	</div>

	<div id="memberful-registered">
		<h1><?php _e( 'Integration Active', 'memberful' ); ?></h1>
		<p><?php printf( __( 'Last automatic sync completed 2 hours ago. Managing %d products, and %d subscriptions.', 'memberful' ), count($products), count($subscriptions)); ?></p>
		<button class="memberful-button-grey"><?php _e( 'Memberful Dashboard', 'memberful' ); ?></button>
		<form style="display: inline;" method="POST" action="<?php echo admin_url('admin.php?page=memberful_options=&noheader=true'); ?>">
			<button type="submit" name="sync_products" class="memberful-button-grey"><?php _e( 'Run Manual Sync', 'memberful' ); ?></button>
			<button type="submit" name="reset_plugin" class="memberful-button-grey"><?php _e( 'Reset plugin', 'memberful' ); ?></button>
		</form>
	</div>
	<div class="memberful-protect-help">
		To protect individual posts and pages based on a member's products or subscriptions, edit the post or page and look for the Memberful meta box.
	</div>
</div>
