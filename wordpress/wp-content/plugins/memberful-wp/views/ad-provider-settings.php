<?php
/**
 * Ad provider settings view.
 *
 * @package memberful-wp
 */

?>
<div class="wrap">
  <?php memberful_wp_render( 'option_tabs', array( 'active' => 'ad_provider_settings' ) ); ?>
  <?php memberful_wp_render( 'flash' ); ?>

  <form method="POST" action="<?php echo esc_url( $form_target ); ?>">
    <?php memberful_wp_nonce_field( 'memberful_options' ); ?>

    <div class="memberful-bulk-apply-box">
      <h3><?php esc_html_e( 'Hide ads for signed in members', 'memberful' ); ?></h3>
      <p><?php
        $memberful_email_link = '<a href="mailto:' . esc_attr( 'info@memberful.com' ) . '">' . esc_html( 'info@memberful.com' ) . '</a>';
        printf(
          /* translators: %s: mailto link to info@memberful.com */
          wp_kses(
            __( 'To hide ads for members, select your ad provider and set the visibility by subscription plan. If you don\'t see your ad provider listed, please email us at %s.', 'memberful' ),
            array(
              'a' => array(
                'href' => array(),
              ),
            )
          ),
          $memberful_email_link
        );
      ?></p>

      <?php if ( empty( $providers ) ) : ?>
        <p><?php esc_html_e( 'No ad providers are registered.', 'memberful' ); ?></p>
      <?php else : ?>
        <?php foreach ( $providers as $provider_id => $provider ) : ?>
          <?php
            $settings       = isset( $provider_settings[ $provider_id ] ) ? $provider_settings[ $provider_id ] : array();
            $settings       = wp_parse_args( $settings, memberful_wp_ad_provider_settings_defaults() );
            $disabled_plans = isset( $settings['disabled_plans'] ) && is_array( $settings['disabled_plans'] )
              ? $settings['disabled_plans']
              : array();
          ?>
          <div class="memberful-ad-provider-settings">
            <p>
              <label for="memberful_ad_provider_<?php echo esc_attr( $provider_id ); ?>_enabled">
                <input
                  id="memberful_ad_provider_<?php echo esc_attr( $provider_id ); ?>_enabled"
                  type="checkbox"
                  name="memberful_ad_provider[<?php echo esc_attr( $provider_id ); ?>][enabled]"
                  <?php checked( ! empty( $settings['enabled'] ) ); ?>
                >
                <strong><?php echo esc_html( $provider->get_name() ); ?></strong>
                <?php if ( ! $provider->is_installed() ) : ?>
                  <span><?php esc_html_e( '(not installed)', 'memberful' ); ?></span>
                <?php endif; ?>
              </label>
            </p>
            <?php if ( 'advanced-ads' === $provider_id ) : ?>
              <p><em><?php esc_html_e( 'Tip: Advanced Ads also has its own role-based "Disable Ads" feature.', 'memberful' ); ?></em></p>
            <?php endif; ?>

            <div
              data-depends-on="memberful_ad_provider_<?php echo esc_attr( $provider_id ); ?>_enabled"
              data-depends-value="1"
              style="display: none;"
            >
              <p>
                <label for="memberful_ad_provider_<?php echo esc_attr( $provider_id ); ?>_disable_for_logged_in">
                  <input
                    id="memberful_ad_provider_<?php echo esc_attr( $provider_id ); ?>_disable_for_logged_in"
                    type="checkbox"
                    name="memberful_ad_provider[<?php echo esc_attr( $provider_id ); ?>][disable_for_logged_in]"
                    <?php checked( ! empty( $settings['disable_for_logged_in'] ) ); ?>
                  >
                  <em><?php esc_html_e( 'Hide ads for all members (active, inactive, or free).', 'memberful' ); ?></em>
                </label>
              </p>
              <p
                data-depends-on="memberful_ad_provider_<?php echo esc_attr( $provider_id ); ?>_disable_for_logged_in"
                data-depends-value-not="1"
              >
                <label for="memberful_ad_provider_<?php echo esc_attr( $provider_id ); ?>_disable_for_all_subscribers">
                  <input
                    id="memberful_ad_provider_<?php echo esc_attr( $provider_id ); ?>_disable_for_all_subscribers"
                    type="checkbox"
                    name="memberful_ad_provider[<?php echo esc_attr( $provider_id ); ?>][disable_for_all_subscribers]"
                    <?php checked( ! empty( $settings['disable_for_all_subscribers'] ) ); ?>
                  >
                  <em><?php esc_html_e( 'Hide ads for any member with an active subscription.', 'memberful' ); ?></em>
                </label>
              </p>
              <div
                data-depends-on="memberful_ad_provider_<?php echo esc_attr( $provider_id ); ?>_disable_for_logged_in"
                data-depends-value-not="1"
              >
                <div
                  data-depends-on="memberful_ad_provider_<?php echo esc_attr( $provider_id ); ?>_disable_for_all_subscribers"
                  data-depends-value-not="1"
                >
                  <p><?php esc_html_e( 'Hide ads for members with an active subscription to a specific plan:', 'memberful' ); ?></p>

                  <?php if ( empty( $subscription_plans ) ) : ?>
                    <p><?php esc_html_e( 'No subscription plans are available.', 'memberful' ); ?></p>
                  <?php else : ?>
                    <ul>
                      <?php foreach ( $subscription_plans as $plan_id => $plan ) : ?>
                        <li>
                          <label>
                            <input
                              type="checkbox"
                              name="memberful_ad_provider[<?php echo esc_attr( $provider_id ); ?>][disabled_plans][]"
                              value="<?php echo esc_attr( $plan_id ); ?>"
                              <?php checked( in_array( (int) $plan_id, $disabled_plans, true ) ); ?>
                            >
                            <?php echo esc_html( $plan['name'] ); ?>
                          </label>
                        </li>
                      <?php endforeach; ?>
                    </ul>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
          <hr>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
    <button type="submit" name="save_ad_provider_settings" class="button button-primary">
      <?php esc_html_e( 'Save Changes', 'memberful' ); ?>
    </button>
  </form>
</div>
