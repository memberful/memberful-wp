<div class="wrap">
  <?php memberful_wp_render('option_tabs', array('active' => 'private_user_feed_settings')); ?>
  <?php memberful_wp_render('flash'); ?>
  <form method="POST" action="<?php echo $form_target ?>">
    <div class="memberful-private-rss-feed-settings-box">
      <h3><?php _e( "Private RSS Feed Settings", 'memberful' ); ?></h3>
      <fieldset>
        <?php if ( ! empty( $subscription_plans ) ) : ?>
          <div id="memberful-private-user-feed-subscription-list">
            <h4><?php _e( 'Feeds are available for:', 'memberful' ); ?></h4>
            <ul>
              <?php foreach($subscription_plans as $id => $subscription): ?>
                <li>
                  <label>
                    <input type="checkbox"
                           name="memberful_private_feed_subscriptions[]"
                           value="<?php echo $id; ?>"
                          <?php checked(in_array($id, memberful_private_user_feed_settings_get_required_plan()));?>
                        >
                    <?php echo esc_html( $subscription['name'] ); ?>
                  </label>
                </li>
              <?php endforeach; ?>
            </ul>
          </div>

          <div class="memberful-private-feed-instructions-container">
            <h4><?php _e( 'Usage instructions:', 'memberful' ); ?></h4>
            <p><?php _e( 'Copy the following where you want to display the feed link:', 'memberful' ); ?> : <strong>[memberful_private_user_feed_link]</strong></p>
          </div>
        <?php else : ?>
          <p class="memberful-private-feed-error"><?php _e( "There are no available subscriptions", 'memberful' ); ?></p>
        <?php endif; ?>
        <p>
          <input type="submit" class="button button-primary" value="<?php _e( "Save Changes", 'memberful' ); ?>" />
        </p>
      </fieldset>
    </div>
    <?php memberful_wp_nonce_field( 'memberful_options' ); ?>
  </form>
</div>
