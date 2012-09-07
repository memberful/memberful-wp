<div id="memberful-wrap" class="wrap">
	<div id="memberful-registration">
		<div class="memberful-sign-up">
			<h1><?php _e( 'A Memberful account is required for setup', 'memberful' ); ?></h1>
			<p><?php _e( '<a href="http://memberful.com">Sign up for an account</a> and start selling digital products and subscriptions the easy way.', 'memberful' ); ?></p>
		</div>

		<div class="memberful-register-plugin">
			<h3><?php _e( 'Already have a Memberful account?', 'memberful' ); ?></h3>
			<form method="POST">
				<fieldset>
					<textarea placeholder="<?php echo esc_attr( __( 'Paste your WordPress registration key here...', 'memberful' ) ); ?>" name="activation_code"></textarea>
					<button class="memberful-button-grey"><?php _e( 'Register this site with your Memberful account', 'memberful' ); ?></button>
					<input type="hidden" name="action" value="register" />
					<?php wp_nonce_field( 'memberful_register' ); ?>
				</fieldset>
			</form>
		</div>
	</div>
</div>
