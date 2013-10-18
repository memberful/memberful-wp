<?php if ( $message = Memberful_Wp_Reporting::pop() ): ?>
<div class="error">
	<p>
		<?php if ( $message['type'] === 'error' ): ?>
		<strong><?php _e( 'Uh oh, something went wrong:' ); ?></strong>
		<?php endif; ?>

		<?php echo $message['message'] ?>
	</p>
</div>
<?php endif; ?>

