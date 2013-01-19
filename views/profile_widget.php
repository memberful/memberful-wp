<?php if ( is_user_logged_in() ): ?>
	<?php echo get_avatar( wp_get_current_user()->user_email, 48 ); ?>
	<div class="memberful-profile">
		<div class="memberful-name"><?php echo wp_get_current_user()->display_name;  ?></div>
		<div class="memberful-profile-links">
			<a href="<?php echo memberful_member_url(); ?>" class="memberful-account-link"><?php echo __('View account'); ?></a> |
			<a href="<?php echo memberful_signout_url(); ?>" class="memberful-sign-out-link"><?php echo __('Sign out'); ?></a>
		</div>
	</div>
<?php else: ?>
	<a href="<?php echo memberful_wp_login_url(); ?>"><?php echo __('Sign in'); ?></a>
<?php endif; ?>
