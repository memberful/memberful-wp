<div class="wrap">
  <?php memberful_wp_render('option_tabs', array('active' => 'plan_role_mappings')); ?>
  <?php memberful_wp_render('flash'); ?>
  
  <h2><?php _e( 'Per-Plan Role Mappings', 'memberful' ); ?></h2>
  <p><?php _e( 'Assign specific WordPress roles to specific subscription plans. This gives you more granular control over member permissions.', 'memberful' ); ?></p>
  
  <form method="post" action="<?php echo esc_url(memberful_wp_plugin_plan_role_mappings_url( TRUE )); ?>">
    <table class="form-table">
      <tr>
        <th scope="row">
          <label for="use_per_plan_roles"><?php _e( 'Enable Per-Plan Roles', 'memberful' ); ?></label>
        </th>
        <td>
          <input type="checkbox" id="use_per_plan_roles" name="use_per_plan_roles" value="1" <?php checked( $use_per_plan_roles ); ?> />
          <p class="description"><?php _e( 'When enabled, users will be assigned roles based on their specific subscription plans instead of just active/inactive status.', 'memberful' ); ?></p>
        </td>
      </tr>
    </table>

    <div id="plan-role-mappings" style="<?php echo $use_per_plan_roles ? '' : 'display: none;'; ?>">
      <h3><?php _e( 'Plan Role Mappings', 'memberful' ); ?></h3>
      <p><?php _e( 'Map each subscription plan to a WordPress role. Users subscribed to a plan will be assigned the corresponding role.', 'memberful' ); ?></p>
      
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
                  <option value="<?php echo esc_attr( $role ); ?>" <?php selected( $current_mappings[ $plan_id ], $role ); ?>>
                    <?php echo esc_html( $role_name ); ?>
                  </option>
                  <?php endforeach; ?>
                </select>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>

    <p class="submit">
      <input type="submit" name="save_plan_role_mappings" class="button button-primary" value="<?php _e( 'Save Changes', 'memberful' ); ?>" />
    </p>
    
    <?php memberful_wp_nonce_field( 'memberful_options' ); ?>
  </form>

  <script type="text/javascript">
  jQuery(document).ready(function($) {
    $('#use_per_plan_roles').change(function() {
      if ($(this).is(':checked')) {
        $('#plan-role-mappings').show();
      } else {
        $('#plan-role-mappings').hide();
      }
    });
  });
  </script>
</div>
