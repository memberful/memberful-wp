<div class="wrap">
	<h2>Memberful Integration Options</h2>
	<form method="POST" action="options.php">
		<?php settings_fields('memberful_wp'); ?>
		<?php do_settings_sections('memberful_wp_settings'); ?>
		<input name="submit" type="submit" value="Save Changes" class="button-primary" />
	</form>
</div>
