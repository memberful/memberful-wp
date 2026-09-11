<?php
/**
 * Metering settings view.
 *
 * @package memberful-wp
 *
 * @var array  $config
 * @var array  $fields
 * @var array  $operators
 * @var array  $post_type_options
 * @var string $form_target
 */

$rules         = isset( $config['rules'] ) && is_array( $config['rules'] ) ? array_values( $config['rules'] ) : array();
$exclude_rules = isset( $config['exclude_rules'] ) && is_array( $config['exclude_rules'] ) ? array_values( $config['exclude_rules'] ) : array();
$render_vars   = compact( 'fields', 'operators', 'post_type_options' );

/**
 * Render the saved groups for one scope. New (unsaved) groups are cloned from
 * the <template> scaffolds at the bottom of the form by metering-admin.js.
 */
$render_groups = function ( $scope, $groups, $intro ) use ( $render_vars ) {
  foreach ( $groups as $group_index => $group ) {
    $conditions = isset( $group['conditions'] ) && is_array( $group['conditions'] ) ? $group['conditions'] : array();
    $match      = isset( $group['match'] ) && in_array( $group['match'], Memberful_Metering_Config::MATCH_TYPES, true ) ? $group['match'] : 'all';

    memberful_wp_render(
      'metering/group',
      array_merge(
        $render_vars,
        array(
          'scope'       => $scope,
          'group_index' => $group_index,
          'match'       => $match,
          'intro'       => $intro,
          'conditions'  => $conditions,
        )
      )
    );
  }
};

$meter_intro   = __( 'Meter content when', 'memberful' );
$exclude_intro = __( 'Exclude when', 'memberful' );
?>
<div class="wrap">
  <?php memberful_wp_render( 'option_tabs', array( 'active' => 'metering' ) ); ?>
  <?php memberful_wp_render( 'flash' ); ?>

  <form method="POST" action="<?php echo esc_url( $form_target ); ?>" class="memberful-metering">
    <?php memberful_wp_nonce_field( 'memberful_options' ); ?>

    <div class="memberful-bulk-apply-box memberful-bulk-apply-box--wide">
      <h3><?php esc_html_e( 'Metering', 'memberful' ); ?></h3>

      <div class="memberful-metering-section">
        <label class="memberful-metering-checkline">
          <input type="checkbox" id="memberful_metering_enabled" name="memberful_metering[enabled]" value="1" <?php checked( ! empty( $config['enabled'] ) ); ?>>
          <span>
            <strong><?php esc_html_e( 'Enable metering', 'memberful' ); ?></strong>
            <span class="description"><?php esc_html_e( 'Turn the meter on or off without losing your rules.', 'memberful' ); ?></span>
          </span>
        </label>
      </div>

      <?php if ( ! empty( $config['enabled'] ) && ! get_option( 'memberful_use_global_marketing' ) ) : ?>
        <div class="notice notice-warning inline">
          <p>
            <?php
            printf(
              /* translators: %s: URL of the Global Paywall settings screen. */
              wp_kses_post( __( 'Metering is on, but the global paywall is disabled, so there is nothing to show visitors who hit the limit and matching posts are not metered. <a href="%s">Turn on the global paywall</a> to start metering.', 'memberful' ) ),
              esc_url( memberful_wp_plugin_global_marketing_url() )
            );
            ?>
          </p>
        </div>
      <?php endif; ?>

      <?php // Hidden while metering is off, like the Global Paywall tab; the rules stay in the form and are saved. ?>
      <div id="memberful_metering_options" data-depends-on="memberful_metering_enabled" data-depends-value="1"<?php if ( empty( $config['enabled'] ) ) echo ' style="display:none"'; ?>>
        <div class="memberful-metering-section">
          <h4 class="memberful-metering-section-heading"><?php esc_html_e( 'Free reading limits', 'memberful' ); ?></h4>
          <p class="memberful-metering-section-intro"><?php esc_html_e( 'How many matching posts a visitor can read before the paywall appears.', 'memberful' ); ?></p>

          <div class="memberful-metering-fields">
            <div class="memberful-metering-field">
              <label class="memberful-metering-field__label" for="memberful_metering_period_days"><?php esc_html_e( 'Reset every', 'memberful' ); ?></label>
              <div class="memberful-metering-field__control">
                <input type="number" min="1" class="small-text" id="memberful_metering_period_days" name="memberful_metering[period_days]" value="<?php echo esc_attr( $config['period_days'] ); ?>">
                <span class="memberful-metering-field__suffix"><?php esc_html_e( 'days', 'memberful' ); ?></span>
              </div>
            </div>

            <div class="memberful-metering-field">
              <div class="memberful-metering-field__label">
                <label for="memberful_metering_anonymous_limit"><?php esc_html_e( 'Anonymous visitors', 'memberful' ); ?></label>
                <span class="description"><?php esc_html_e( "People who haven't signed up.", 'memberful' ); ?></span>
              </div>
              <div class="memberful-metering-field__control">
                <input type="number" min="0" max="<?php echo esc_attr( Memberful_Metering_Storage::MAX_VIEWS ); ?>" class="small-text" id="memberful_metering_anonymous_limit" name="memberful_metering[anonymous_limit]" value="<?php echo esc_attr( $config['anonymous_limit'] ); ?>">
              </div>
            </div>

            <div class="memberful-metering-field">
              <div class="memberful-metering-field__label">
                <label for="memberful_metering_registered_limit"><?php esc_html_e( 'Free members', 'memberful' ); ?></label>
                <span class="description"><?php esc_html_e( 'Registered, but not on a paid plan.', 'memberful' ); ?></span>
              </div>
              <div class="memberful-metering-field__control">
                <input type="number" min="0" max="<?php echo esc_attr( Memberful_Metering_Storage::MAX_VIEWS ); ?>" class="small-text" id="memberful_metering_registered_limit" name="memberful_metering[registered_limit]" value="<?php echo esc_attr( $config['registered_limit'] ); ?>">
              </div>
            </div>

            <div class="memberful-metering-field">
              <span class="memberful-metering-field__label"><?php esc_html_e( 'Members-only posts', 'memberful' ); ?></span>
              <div class="memberful-metering-field__control">
                <label class="memberful-metering-checkline">
                  <input type="checkbox" id="memberful_metering_apply_to_protected_posts" name="memberful_metering[apply_to_protected_posts]" value="1" <?php checked( ! empty( $config['apply_to_protected_posts'] ) ); ?>>
                  <span>
                    <?php esc_html_e( "Also count members-only posts toward a visitor's free allowance", 'memberful' ); ?>
                    <span class="description"><?php esc_html_e( "When off (recommended), members-only posts always show the paywall and don't use up any of the free reads above. When on, non-members can sample members-only posts — each view uses one free read, and the membership paywall appears once those run out.", 'memberful' ); ?></span>
                    <span class="description"><?php esc_html_e( 'Sampled members-only pages are served non-cacheable. If your host or CDN caches pages for logged-out visitors regardless, a sampled post could be cached in full and shown to everyone, so leave this off unless your caching setup honours no-store.', 'memberful' ); ?></span>
                  </span>
                </label>
              </div>
            </div>
          </div>
        </div>

        <div class="memberful-metering-section">
          <h4 class="memberful-metering-section-heading"><?php esc_html_e( 'What counts toward the meter', 'memberful' ); ?></h4>
          <p class="memberful-metering-section-intro"><?php esc_html_e( 'Include the content you want to meter, then list any exceptions to leave out.', 'memberful' ); ?></p>

          <div class="memberful-metering-ruleset memberful-metering-ruleset--include">
            <h5 class="memberful-metering-ruleset__label"><?php esc_html_e( 'Include', 'memberful' ); ?></h5>
            <div class="memberful-metering-groups" id="memberful-metering-include-groups" data-scope="rules" data-next-group-index="<?php echo esc_attr( count( $rules ) ); ?>">
              <p class="memberful-metering-empty"<?php echo empty( $rules ) ? '' : ' hidden'; ?>><?php esc_html_e( 'Nothing is metered yet. Add a rule group to choose what counts.', 'memberful' ); ?></p>
              <?php $render_groups( 'rules', $rules, $meter_intro ); ?>
            </div>
            <button type="button" class="button memberful-metering-add-group" id="memberful-metering-add-group">+ <?php esc_html_e( 'Add rule group', 'memberful' ); ?></button>
          </div>

          <div class="memberful-metering-ruleset memberful-metering-ruleset--except">
            <h5 class="memberful-metering-ruleset__label"><?php esc_html_e( 'Except', 'memberful' ); ?></h5>
            <div class="memberful-metering-groups" id="memberful-metering-exclude-groups" data-scope="exclude_rules" data-next-group-index="<?php echo esc_attr( count( $exclude_rules ) ); ?>">
              <?php $render_groups( 'exclude_rules', $exclude_rules, $exclude_intro ); ?>
            </div>
            <button type="button" class="button memberful-metering-add-group" id="memberful-metering-add-exclude-group">+ <?php esc_html_e( 'Add exception', 'memberful' ); ?></button>
          </div>

          <details class="memberful-metering-notes">
            <summary class="memberful-metering-notes__summary">
              <svg class="memberful-metering-notes__chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
              <span><?php esc_html_e( 'Caching & hosting notes', 'memberful' ); ?></span>
            </summary>
            <div class="memberful-metering-notes__body">
              <p><?php esc_html_e( 'Metered pages vary per visitor, so Memberful serves them as non-cacheable (no-store / DONOTCACHEPAGE). Page-cache plugins such as WP Super Cache, W3 Total Cache, WP Rocket and LiteSpeed honour this automatically. Edge caches such as Cloudflare or Varnish must be configured to bypass metered URLs, or one visitor\'s view could be cached and shown to everyone.', 'memberful' ); ?></p>
              <p><?php esc_html_e( 'Hosts whose edge cache ignores no-store for logged-out visitors, such as WP Engine\'s Edge Full Page Cache, are not supported for anonymous metering in this release. Metering of signed-in members is unaffected because their pages are never page-cached.', 'memberful' ); ?></p>
              <p><?php esc_html_e( 'Anonymous visitors are counted with a signed cookie, so clearing cookies or switching browsers starts a fresh allowance. Treat the meter as a conversion tool for public content rather than an access-control boundary: members-only content stays protected by your normal access rules unless you opt in above.', 'memberful' ); ?></p>
            </div>
          </details>
        </div>
      </div>
    </div>

    <?php submit_button( __( 'Save Changes', 'memberful' ), 'primary', 'save_metering' ); ?>

    <template id="memberful-metering-rules-group-template">
      <?php
      memberful_wp_render(
        'metering/group',
        array_merge(
          $render_vars,
          array(
            'scope'       => 'rules',
            'group_index' => '__GROUP__',
            'match'       => 'all',
            'intro'       => $meter_intro,
            'conditions'  => array(),
          )
        )
      );
      ?>
    </template>

    <template id="memberful-metering-exclude_rules-group-template">
      <?php
      memberful_wp_render(
        'metering/group',
        array_merge(
          $render_vars,
          array(
            'scope'       => 'exclude_rules',
            'group_index' => '__GROUP__',
            'match'       => 'any',
            'intro'       => $exclude_intro,
            'conditions'  => array(),
          )
        )
      );
      ?>
    </template>

    <template id="memberful-metering-condition-template">
      <?php
      memberful_wp_render(
        'metering/condition',
        array_merge(
          $render_vars,
          array(
            'scope'           => '__SCOPE__',
            'group_index'     => '__GROUP__',
            'condition_index' => '__COND__',
            'condition'       => array( 'field' => 'post_type', 'operator' => 'is_any_of', 'values' => array() ),
          )
        )
      );
      ?>
    </template>

    <template id="memberful-metering-chip-template">
      <?php memberful_wp_render( 'metering/chip' ); ?>
    </template>
  </form>
</div>
