    <div class="memberful-acl-block">
      <label>
        <input type="checkbox" name="memberful_viewable_by_any_registered_users" value="1" <?php if(isset($viewable_by_any_registered_users) && $viewable_by_any_registered_users): ?>checked="checked"<?php endif; ?> />
        Any registered user
      </label>
    </div>
    <div data-depends-on="memberful_viewable_by_any_registered_users" data-depends-value-not="1">
      <?php if ( ! empty( $subscriptions ) ) : ?>
        <div id="memberful-subscriptions" class="memberful-acl-block">
          <p class="memberful-access-label"><?php _e( 'Anybody subscribed to:', 'memberful' ); ?></p>
          <ul>
          <?php foreach($subscriptions as $id => $subscription): ?>
            <li>
              <label>
                <input type="checkbox" name="memberful_subscription_acl[]" value="<?php echo $id; ?>" <?php checked( $subscription['checked'] ); ?>>
                <?php echo esc_html( $subscription['name'] ); ?>
              </label>
            </li>
          <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>
      <?php if ( ! empty( $products ) ) : ?>
        <div id="memberful-downloads" class="memberful-acl-block">
          <p class="memberful-access-label"><?php _e( 'Anybody who owns:', 'memberful' ); ?></p>
          <ul>
          <?php foreach($products as $id => $product): ?>
            <li>
              <label>
                <input type="checkbox" name="memberful_product_acl[]" value="<?php echo $id; ?>" <?php checked( $product['checked'] ); ?>>
                <?php echo esc_html( $product['name'] ); ?>
              </label>
            </li>
          <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>
    </div>
