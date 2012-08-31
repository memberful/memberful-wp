<div id="memberful-wrap" class="wrap">
	<div id="memberful-head">
		<h2>Memberful<a href="#" class="memberful-visit-dashboard" target="_blank"><?php _e( 'Visit Dashboard', 'memberful' ); ?></a></h2>
	</div>

	<div id="memberful_registration">
		<div class="memberful_view-plans">
			<h1><?php _e( 'A Memberful account is required for setup.', 'memberful' ); ?></h1>
			<p><?php _e( '<a href="#">Sign up for free</a> and start selling digital products and subscriptions the easy way.', 'memberful' ); ?></p>
		</div>

		<div class="memberful_register-plugin">
			<h3><?php _e( 'Already have a Memberful account?', 'memberful' ); ?></h3>
			<p><?php _e( 'Paste your registration key below:', 'memberful' ); ?></p>
			<form>
				<fieldset>
					<textarea placeholder="<?php echo esc_attr( __( 'Enter your key here...', 'memberful' ) ); ?>" name="registration_key"></textarea>
					<button><?php _e( 'Register ', 'memberful' ); ?></button>
					<input type="hidden" name="action" value="register" />
					<?php wp_nonce_field( 'memberful_register' ); ?>
				</fieldset>
			</form>
		</div>
	</div>

	<?php if($show_products): ?>
		<div class="products" style="float:left; width: 45%; margin-left: 2%;">
			<p>Here are the products that we've synced from Memberful</p>
			<form method="POST">
				<?php if( ! empty($products)): ?>
				<ul>
				<?php foreach((array) get_option('memberful_products') as $id => $product): ?>
					<li><a href="<?php echo memberful_admin_product_url($id); ?>"><?php echo $product['name']; ?></a></li>
				<?php endforeach; ?>
				</ul>
				<?php endif; ?>
				<input name="refresh_products" type="submit" value="Sync products" class="button-primary" />
			</form>
		</div>
	<?php endif; ?>
</div>