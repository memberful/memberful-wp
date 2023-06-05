<div class="wrap">
  <?php memberful_wp_render('option_tabs', array('active' => 'private_user_feed_settings')); ?>
  <?php memberful_wp_render('flash'); ?>
  <div id="memberful-wrap">
    <form method="POST" action="<?php echo esc_url($form_target); ?>">
      <div class="memberful-private-rss-feed-settings-box">
        <div class="postbox memberful-postbox">
          <fieldset>
            <?php if ( ! empty( $subscription_plans ) ) : ?>
              <div id="memberful-private-user-feed-subscription-list">
                <h3><?php _e( 'Enable private user RSS feeds', 'memberful' ); ?></h3>
                <p><?php _e( "Provide a private RSS feed for active subscribers to the following Plans. Members will get access to <strong>ALL POSTS</strong> on the site, regardless of which plan they are subscribed to.", 'memberful' ); ?></p>
                <ul>
                  <?php foreach($subscription_plans as $id => $subscription): ?>
                    <li>
                      <label>
                        <input type="checkbox"
                               name="memberful_private_feed_subscriptions[]"
                               value="<?php echo esc_attr($id); ?>"
                              <?php checked(in_array($id, $current_feed_subscriptions));?>
                            >
                        <?php echo esc_html( $subscription['name'] ); ?>
                      </label>
                    </li>
                  <?php endforeach; ?>
                </ul>
              </div>
            <?php else : ?>
              <p class="memberful-private-feed-error"><?php _e( "There are no available Subscription Plans.", 'memberful' ); ?></p>
            <?php endif; ?>
            <hr>
            <div class="memberful-add-block-tags">
              <label for="memberful_add_block_tags_to_rss_feed" class="memberful-access-label">
                <input id="memberful_add_block_tags_to_rss_feed" type="checkbox" name="memberful_add_block_tags_to_rss_feed" class="memberful-label__checkbox--multiline" <?php if( $add_block_tags_to_rss_feed ): ?>checked="checked"<?php endif; ?>>
                <span class="memberful-label__text--multiline">Block private RSS feeds from the iTunes and Google podcast directories.</span>
              </label>
            </div>
            <input type="submit" name="memberful_private_feed_subscriptions_submit" class="button button-primary" value="<?php _e( "Save Changes", 'memberful' ); ?>" />
          </fieldset>
        </div>
        <div class="memberful-private-feed-instructions postbox">
          <h3><?php _e( 'Display the private RSS Feed link to members with access', 'memberful' ); ?></h3>
          <p><?php _e( 'Show a private user RSS Feed link to a WordPress post or page:', 'memberful' ); ?></p>
          <p><code><?php _e( '[memberful_private_rss_feed_link]Your RSS feed[/memberful_private_rss_feed_link]', 'memberful' ); ?></code></p>
          <p><?php _e( 'Output a private user RSS Feed link in your WordPress theme:', 'memberful' ); ?></p>
          <p><code><?php _e( '&lt;?php memberful_private_rss_feed_link( "Your RSS Feed", "You don\'t have access." ); ?&gt;', 'memberful' ); ?></code></p>
        </div>
      </div>
      <?php memberful_wp_nonce_field( 'memberful_options' ); ?>
    </form>
  </div>
</div>
