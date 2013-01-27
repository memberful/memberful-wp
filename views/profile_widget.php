<?php if ( is_user_logged_in() ): ?>
	<div class="memberful-profile-gravatar">
		<?php echo get_avatar( wp_get_current_user()->user_email, 48 ); ?>
	</div>
	<div class="memberful-profile-info">
		<div class="memberful-profile-name"><?php echo wp_get_current_user()->display_name;  ?></div>
		<div class="memberful-profile-links">
			<a href="<?php echo memberful_account_url(); ?>" class="memberful-account-link"><?php echo __( 'Account' ); ?></a> |
			<a href="<?php echo memberful_sign_out_url(); ?>" class="memberful-sign-out-link"><?php echo __( 'Sign out' ); ?></a>
		</div>
	</div>
<?php else: ?>
	<a href="<?php echo memberful_sign_in_url(); ?>"><?php echo __( 'Sign in' ); ?></a>
<?php endif; ?>
