<div class="wrap">
  <?php memberful_wp_render('option_tabs', array('active' => 'settings')); ?>
  <?php memberful_wp_render('flash'); ?>
  <div id="memberful-wrap">
    <div id="memberful-registered" class="postbox">
      <h1><?php _e( 'Integration Active', 'memberful' ); ?></h1>
      <h2><?php printf( __( 'Syncing %d plans, %d downloads and %d podcasts.', 'memberful' ), count( $subscriptions ), count( $products ), count( $feeds ) ); ?></h2>
      <p><?php printf( __( '<a href="%s">Sign in to your Memberful account</a> to manage products, subscriptions, members, and orders.' ), memberful_url( 'admin' ) ) ?></p>
      <form method="POST" action="<?php echo esc_url(memberful_wp_plugin_settings_url(TRUE)); ?>">
        <?php memberful_wp_nonce_field( 'memberful_options' ); ?>
        <button type="submit" name="manual_sync" class="button action"><?php _e( 'Run manual sync', 'memberful' ); ?></button>
        <button type="submit" name="reset_plugin" class="memberful-red-button"><?php _e( 'Disconnect', 'memberful' ); ?></button>
      </form>
    </div>
    <div class="memberful-protect-help postbox">
      <?php _e( "To protect content, edit a post or page and look for the <em>Memberful: Restrict Access</em> box.", 'memberful' ); ?>
    </div>

    <div class="postbox memberful-postbox">
      <h1>Settings</h1>
      <p>Customize the appearance and behavior of the Memberful plugin.</p>

      <form method="POST" action="<?php echo esc_url(memberful_wp_plugin_settings_url(TRUE)); ?>">
        <?php memberful_wp_nonce_field( 'memberful_options' ); ?>
        <p>
          <label for="extended_login_period_checkbox">
            <input id="extended_login_period_checkbox" class="memberful-label__checkbox--multiline" type="checkbox" name="extend_auth_cookie_expiration" <?php if( $extend_auth_cookie_expiration ): ?>checked="checked"<?php endif; ?>>
            <span class="memberful-label__text--multiline">Keep all WordPress users logged in for 1 year.</span>
          </label>
        </p>
        <p>
          <label for="hide_admin_toolbar_checkbox">
            <input id="hide_admin_toolbar_checkbox" class="memberful-label__checkbox--multiline" type="checkbox" name="memberful_hide_admin_toolbar" <?php if( $hide_admin_toolbar): ?>checked="checked"<?php endif; ?>>
            <span class="memberful-label__text--multiline">Hide the WordPress admin toolbar from members.</span>
          </label>
        </p>
        <p>
          <label for="block_dashboard_access_checkbox">
            <input id="block_dashboard_access_checkbox" class="memberful-label__checkbox--multiline" type="checkbox" name="memberful_block_dashboard_access" <?php if( $block_dashboard_access): ?>checked="checked"<?php endif; ?>>
            <span class="memberful-label__text--multiline">Block WordPress dashboard access from members.</span>
          </label>
        </p>
        <p>
          <label for="filter_account_menu_items_checkbox">
            <input id="filter_account_menu_items_checkbox" class="memberful-label__checkbox--multiline" type="checkbox" name="memberful_filter_account_menu_items" <?php if( $filter_account_menu_items): ?>checked="checked"<?php endif; ?>>
            <span class="memberful-label__text--multiline">Conditionally show "Sign in," "Sign out," and "Account" menu items based on members' signed-in status.</span>
          </label>
        </p>
        <p>
          <label for="auto_sync_display_names_checkbox">
            <input id="auto_sync_display_names_checkbox" class="memberful-label__checkbox--multiline" type="checkbox" name="memberful_auto_sync_display_names" <?php if( $auto_sync_display_names): ?>checked="checked"<?php endif; ?>>
            <span class="memberful-label__text--multiline">Update display names in WordPress when members change their full name in Memberful.</span>
          </label>
        </p>
        <button type="submit" name="save_changes" class="button button-primary">Save Changes</button>
      </form>
    </div>
  </div>
</div>
