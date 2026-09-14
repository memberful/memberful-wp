<div class="wrap">
  <?php memberful_wp_render('option_tabs', array('active' => 'advanced_settings')); ?>
  <?php memberful_wp_render('flash'); ?>
  <p class="memberful-role-mapping-table-description" style="<?php echo $use_per_plan_roles ? 'display: none;' : ''; ?>"><?php _e( "Assign roles to active (paying) and inactive (not paying) members. Memberful will automatically keep the role mappings in sync. Works best with custom roles created by other plugins.", 'memberful' ); ?></p>
  <form method="post" action="<?php echo esc_url(memberful_wp_plugin_advanced_settings_url( TRUE )); ?>">
      <table class="widefat fixed" id="memberful-role-mapping-table" style="<?php echo $use_per_plan_roles ? 'display: none;' : ''; ?>">
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

      <h2 style="margin-top:20px;">Per-Plan Roles</h2>
      <p><?php _e( 'Assign specific WordPress roles to specific subscription plans. Enable this to override the simple active/inactive role mapping.', 'memberful' ); ?></p>

      <table class="form-table">
        <tr>
          <th scope="row">
            <label for="use_per_plan_roles"><?php _e( 'Enable Per-Plan Roles', 'memberful' ); ?></label>
          </th>
          <td>
            <input type="checkbox" id="use_per_plan_roles" name="use_per_plan_roles" value="1" <?php checked( $use_per_plan_roles ); ?> />
          </td>
        </tr>
      </table>

      <div id="plan-role-mappings" style="<?php echo $use_per_plan_roles ? '' : 'display: none;'; ?>">
        <?php if ( empty( $subscription_plans ) ): ?>
          <p class="description"><?php _e( 'No subscription plans found. Please sync your plans from Memberful first.', 'memberful' ); ?></p>
        <?php else: ?>
          <table class="widefat fixed" id="memberful-plan-role-mapping-table">
            <thead>
              <tr>
                <th scope="col" class="manage-column"><?php _e( 'Subscription Plan', 'memberful' ); ?></th>
                <th scope="col" class="manage-column"><?php _e( 'WordPress Role', 'memberful' ); ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach( $subscription_plans as $plan_id => $plan ): ?>
              <tr>
                <td class="plan-name">
                  <strong><?php echo esc_html( $plan['name'] ); ?></strong>
                  <?php if ( ! empty( $plan['description'] ) ): ?>
                  <br>
                  <small><?php echo esc_html( $plan['description'] ); ?></small>
                  <?php endif; ?>
                </td>
                <td class="mapped-role">
                  <select name="plan_role_mappings[<?php echo esc_attr( $plan_id ); ?>]">
                    <option value=""><?php _e( 'No specific role (use default)', 'memberful' ); ?></option>
                    <?php foreach( $available_roles as $role => $role_name ): ?>
                    <option value="<?php echo esc_attr( $role ); ?>" <?php echo ( isset($current_mappings[$plan_id]) && $current_mappings[$plan_id] === $role ) ? 'selected="selected"' : '' ?>>
                      <?php echo esc_html( $role_name ); ?>
                    </option>
                    <?php endforeach; ?>
                  </select>
                </td>
              </tr>
              <?php endforeach; ?>
              <tr>
                <td class="plan-name">
                  <strong><?php _e( 'No active subscription plan', 'memberful' ); ?></strong>
                </td>
                <td class="mapped-role">
                  <select name="plan_role_mappings[inactive]">
                    <option value=""><?php _e( 'No specific role (use default)', 'memberful' ); ?></option>
                    <?php foreach( $available_roles as $role => $role_name ): ?>
                    <option value="<?php echo esc_attr( $role ); ?>" <?php echo ( isset($current_mappings['inactive']) && $current_mappings['inactive'] === $role ) ? 'selected="selected"' : '' ?>>
                      <?php echo esc_html( $role_name ); ?>
                    </option>
                    <?php endforeach; ?>
                  </select>
                </td>
              </tr>
            </tbody>
          </table>
        <?php endif; ?>
      </div>

      <p class="button-controls"><input type="submit" name="nav-menu-locations" id="nav-menu-locations" class="button button-primary left" value="Save Changes"></p>
      <?php memberful_wp_nonce_field( 'memberful_options' ); ?>
    </form>
  <script type="text/javascript">
  jQuery(document).ready(function($) {
    function togglePerPlanUI() {
      var enabled = $('#use_per_plan_roles').is(':checked');
      $('#plan-role-mappings').toggle(enabled);
      $('#memberful-role-mapping-table').toggle(!enabled);
      $('.memberful-role-mapping-table-description').toggle(!enabled);
    }

    $('#use_per_plan_roles').on('change', togglePerPlanUI);
    togglePerPlanUI();
  });
  </script>
</div>
