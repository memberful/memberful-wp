<?php settings_errors(); ?>
<div>
    <form method="post" action="options.php" class="settings-page">
        <?php do_settings_sections( ARIADMINER_SETTINGS_GENERAL_PAGE ); ?>

        <button type="submit" class="button button-primary"><?php _e( 'Save Changes', 'ari-adminer' ); ?></button>
        <?php settings_fields( ARIADMINER_SETTINGS_GROUP ); ?>
    </form>
</div>