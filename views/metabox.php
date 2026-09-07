<?php if ( ! empty( $subscriptions ) || ! empty( $products ) ) : ?>
  <div class="memberful-restrict-access">
    <div class="memberful-restrict-access-options">
      <h4 style="font-size: 13px;"><?php _e( 'Who has access?', 'memberful' ); ?></h4>
      <?php memberful_wp_render( 'acl_selection', compact( 'subscriptions', 'products', 'viewable_by_any_registered_users', 'viewable_by_anybody_subscribed_to_a_plan' ) ); ?>
    </div>
    <div class="memberful-marketing-content">
      <?php if ( ! empty( $global_marketing_overrides_post_content ) ) : ?>
        <div class="notice notice-info inline">
          <p>
            <?php
            printf(
              wp_kses(
                /* translators: %s: URL to the global marketing settings screen */
                __( 'Marketing content is currently controlled by the <a href="%s">global marketing settings</a>.', 'memberful' ),
                array( 'a' => array( 'href' => array() ) )
              ),
              esc_url( memberful_wp_plugin_global_marketing_url() )
            );
            ?>
          </p>
        </div>
      <?php else : ?>
        <?php
        $editor_id = 'memberful_marketing_content';
        $settings  = array();
        wp_editor( $marketing_content, $editor_id, $settings );
        ?>
        <div class="memberful-marketing-content-description">
          <a href="<?php echo esc_url( memberful_wp_plugin_global_marketing_url() ); ?>">
            <?php esc_html_e( 'Manage global paywall', 'memberful' ); ?>
          </a>
        </div>
      <?php endif; ?>
    </div>
  </div>
<?php else: ?>
  <div>
    <p><em><?php esc_html_e( "We couldn't find any products or subscriptions in your Memberful account. You'll need to add some before you can restrict access.", 'memberful' ); ?></em></p>
  </div>
<?php endif; ?>
