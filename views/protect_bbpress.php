<div class="wrap">
  <?php memberful_wp_render('option_tabs', array('active' => 'protect_bbpress')); ?>
  <?php memberful_wp_render('flash'); ?>
  <form method="POST" action="<?php echo esc_url($form_target); ?>">
    <?php memberful_wp_nonce_field( 'memberful_options' ); ?>
    <div class="memberful-bbpress-enable">
      <label>
        <input type="hidden" name="memberful_protect_bbpress" value="0" />
        <input type="checkbox" name="memberful_protect_bbpress" value="1" <?php checked($protect_bbpress) ?>/>
        <?php _e('Protect your bbPress forums with Memberful','memberful'); ?>
      </label>
    </div>
    <div class="memberful-restrict-access-options memberful-bbpress-acl" data-depends-on="memberful_protect_bbpress" data-depends-value="1">
      <h4><?php _e('Who can access forums?', 'memberful') ?></h4>
      <?php
      memberful_wp_render(
        'acl_selection',
        array(
          'subscriptions' => $plans,
          'products' => $downloads,
          'viewable_by_any_registered_users' => $restricted_to_registered_users,
          'viewable_by_anybody_subscribed_to_a_plan' => $restricted_to_subscribed_users
        )
      );
      ?>
    </div>
    <div class="memberful-bbpress-redirect" data-depends-on="memberful_protect_bbpress" data-depends-value="1">
      <h4><?php _e('Where should users without access be sent?', 'memberful'); ?></h4>
      <div>
        <fieldset>
          <div>
            <label>	
              <input type="radio" name="memberful_send_unauthorized_users" value="homepage" <?php checked($send_unauthorized_users_to_homepage); ?> />
              <?php _e('Send them to the homepage', 'memberful'); ?>
            </label>
          </div>
          <div>
            <label>
              <input type="radio" name="memberful_send_unauthorized_users" value="url" <?php checked(!$send_unauthorized_users_to_homepage); ?> />
              <?php _e('Send them to a specific URL', 'memberful'); ?>
            </label>
          </div>
          <div class="memberful-bbpress-redirect-custom-url" data-depends-on="memberful_send_unauthorized_users" data-depends-value="url">
            <input type="text" id="memberful_send_unauthorized_users_to_url" name="memberful_send_unauthorized_users_to_url" value="<?php echo esc_attr($send_unauthorized_users_to_url); ?>" placeholder="http://mysite.com/signup">
          </div>
        </fieldset>
      </div>
    </div>
    <div class="clear"></div>
    <div class="submit-buttons memberful-bbpress-submit-buttons">
      <input type="submit" class="button button-primary" value="<?php _e( "Apply settings", 'memberful' ); ?>" />
    </div>
  </form>
</div>
