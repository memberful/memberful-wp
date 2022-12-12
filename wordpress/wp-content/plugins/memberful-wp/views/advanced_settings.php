<div class="wrap">
  <?php memberful_wp_render('option_tabs', array('active' => 'advanced_settings')); ?>
  <?php memberful_wp_render('flash'); ?>
  <p><?php _e( "Assign roles to active (paying) and inactive (not paying) members. Memberful will automatically keep the role mappings in sync. Works best with custom roles created by other plugins.", 'memberful' ); ?></p>
  <form method="post" action="<?php echo esc_url(memberful_wp_plugin_advanced_settings_url( TRUE )); ?>">
      <table class="widefat fixed" id="memberful-role-mapping-table">
        <thead>
        <tr>
          <th scope="col" class="manage-column"><?php _e( "Map members with", 'memberful' ); ?></th>
          <th scope="col" class="manage-column"><?php _e( "To this role", 'memberful' ); ?></th>
        </tr>
        </thead>
        <tbody class="role-mapping">
          <?php foreach( $available_state_mappings as $state_id => $state): ?>
          <tr>
            <td class="customer-state"><strong><?php echo esc_html($state['name']); ?></strong></td>
            <td class="mapped-role">
              <select name="role_mappings[<?php echo esc_attr($state_id); ?>]">
                <?php foreach( $available_roles as $role => $role_name ): ?>
                <option value="<?php echo esc_attr($role); ?>" <?php echo ($state['current_role'] === $role) ? 'selected="selected"' : '' ?>><?php echo esc_html($role_name); ?></option>
                <?php endforeach; ?>
              </select>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <p class="button-controls"><input type="submit" name="nav-menu-locations" id="nav-menu-locations" class="button button-primary left" value="Save Changes"></p>
      <?php memberful_wp_nonce_field( 'memberful_options' ); ?>
    </form>
</div>
