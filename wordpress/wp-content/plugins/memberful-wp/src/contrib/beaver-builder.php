<?php
/**
 * Beaver Builder integration.
 *
 * Adds Memberful visibility settings to the Advanced tab of Beaver Builder rows, columns, and modules, mirroring the
 * Gutenberg block visibility controls in src/block-editor.php.
 *
 * Also keeps Beaver Builder from rendering a protected layout in place of, or inside, the Memberful paywall.
 *
 * Restricted module text stays in the plain-text fallback Beaver Builder saves to `post_content`, the same as block
 * visibility and post-level protection, so site search still matches it. Beaver Builder drops nodes with its native
 * rules from that fallback; Memberful rules deliberately do not.
 *
 * Hook callbacks leave the filtered value untyped on purpose: a wrong type returned by another plugin's callback on the
 * same hook should fail in that plugin, not as a TypeError here.
 */

add_action( 'plugins_loaded', 'memberful_wp_beaver_builder_init' );

function memberful_wp_beaver_builder_init() {
  if ( ! class_exists( 'FLBuilderModel' ) ) {
    return;
  }

  add_filter( 'fl_builder_register_settings_form', 'memberful_wp_beaver_builder_add_visibility_section', 10, 2 );
  add_filter( 'fl_builder_is_node_visible', 'memberful_wp_beaver_builder_is_node_visible', 10, 2 );
  add_filter( 'fl_builder_do_render_content', 'memberful_wp_beaver_builder_do_render_content', 10, 2 );

  // The `memberful_wp_protect_content` filter only runs while a paywall is being built, so it doubles as the
  // "this post is paywalled" signal.
  add_filter( 'memberful_wp_protect_content', 'memberful_wp_beaver_builder_remember_paywalled_post' );
}

/**
 * Add the Memberful Visibility section to the Advanced tab of every Beaver Builder row, column, and module settings form.
 *
 * @param array  $form The settings form config, as filtered so far.
 * @param string $id   The form id ("row", "col", or "module_advanced").
 * @return array The settings form config.
 */
function memberful_wp_beaver_builder_add_visibility_section( $form, string $id ): array {
  if ( 'row' === $id || 'col' === $id ) {
    $form['tabs']['advanced']['sections']['memberful_visibility'] = memberful_wp_beaver_builder_visibility_section();
  }

  if ( 'module_advanced' === $id ) {
    $form['sections']['memberful_visibility'] = memberful_wp_beaver_builder_visibility_section();
  }

  return $form;
}

/**
 * The Memberful Visibility settings section config.
 *
 * @return array The section config.
 */
function memberful_wp_beaver_builder_visibility_section(): array {
  $plan_options = array();

  foreach ( memberful_subscription_plans() as $plan_id => $plan ) {
    if ( isset( $plan['name'] ) ) {
      // Beaver Builder expects pre-escaped option labels.
      $plan_options[ (string) $plan_id ] = esc_html( $plan['name'] );
    }
  }

  return array(
    'title'  => __( 'Memberful Visibility', 'memberful' ),
    'fields' => array(
      'memberful_visibility'       => array(
        'type'    => 'select',
        'label'   => __( 'Applies to', 'memberful' ),
        'default' => '',
        'options' => array(
          ''          => __( 'Everyone', 'memberful' ),
          'logged_in' => __( 'Any logged in member', 'memberful' ),
          'specific'  => __( 'Members on specific plans', 'memberful' ),
        ),
        'toggle'  => array(
          'logged_in' => array( 'fields' => array( 'memberful_visibility_hide' ) ),
          'specific'  => array( 'fields' => array( 'memberful_visibility_hide', 'memberful_visibility_plans' ) ),
        ),
        'preview' => array( 'type' => 'none' ),
      ),
      'memberful_visibility_hide'  => array(
        'type'    => 'select',
        'label'   => __( 'Action', 'memberful' ),
        'default' => '',
        'options' => array(
          ''  => __( 'Show', 'memberful' ),
          '1' => __( 'Hide', 'memberful' ),
        ),
        'preview' => array( 'type' => 'none' ),
      ),
      'memberful_visibility_plans' => array(
        'type'         => 'select',
        'label'        => __( 'Plans', 'memberful' ),
        'options'      => $plan_options,
        'multi-select' => true,
        'help'         => __( 'Applies to members with an active subscription to at least one of the selected plans. If no plans are selected, the element stays visible to all logged in members.', 'memberful' ),
        'preview'      => array( 'type' => 'none' ),
      ),
    ),
  );
}

/**
 * Apply the Memberful visibility rule when Beaver Builder decides whether to render a row, column, or module.
 *
 * @param bool   $is_visible Whether Beaver Builder considers the node visible, as filtered so far.
 * @param object $node       The node.
 * @return bool Whether the node should be rendered.
 */
function memberful_wp_beaver_builder_is_node_visible( $is_visible, object $node ): bool {
  if ( ! $is_visible ) {
    return FALSE;
  }

  // Always show nodes while editing in the builder UI.
  if ( FLBuilderModel::is_builder_active() ) {
    return TRUE;
  }

  $rule = isset( $node->settings->memberful_visibility ) ? $node->settings->memberful_visibility : '';

  if ( 'logged_in' === $rule ) {
    return memberful_wp_beaver_builder_logged_in_rule_allows( $node->settings );
  }

  if ( 'specific' === $rule ) {
    return memberful_wp_beaver_builder_specific_plans_rule_allows( $node->settings );
  }

  return TRUE;
}

/**
 * Any logged in member rule, optionally reversed by the hide flag.
 *
 * @param object $settings The node settings.
 * @return bool Whether the node should be rendered.
 */
function memberful_wp_beaver_builder_logged_in_rule_allows( object $settings ): bool {
  if ( ! empty( $settings->memberful_visibility_hide ) ) {
    return ! is_user_logged_in();
  }

  return is_user_logged_in();
}

/**
 * Specific plans rule, optionally reversed by the hide flag.
 *
 * @param object $settings The node settings.
 * @return bool Whether the node should be rendered.
 */
function memberful_wp_beaver_builder_specific_plans_rule_allows( object $settings ): bool {
  if ( ! is_user_logged_in() ) {
    return FALSE;
  }

  $plans = isset( $settings->memberful_visibility_plans ) ? array_filter( (array) $settings->memberful_visibility_plans ) : array();

  // No plans configured - fall back to rendering the node unmodified.
  if ( empty( $plans ) ) {
    return TRUE;
  }

  $has_plan = memberful_wp_user_has_subscription_to_plans( wp_get_current_user()->ID, $plans );

  if ( ! empty( $settings->memberful_visibility_hide ) ) {
    // Hide the node if the user has any of the specific plans.
    return ! $has_plan;
  }

  return $has_plan;
}

/**
 * Posts Memberful has paywalled during this request, keyed by the post ID Beaver Builder renders layouts for.
 *
 * @param int|null $add A post ID to record.
 * @return array<int, true>
 */
function memberful_wp_beaver_builder_paywalled_post_ids( ?int $add = null ): array {
  static $ids = array();

  if ( null !== $add && $add > 0 ) {
    $ids[ $add ] = true;
  }

  return $ids;
}

/**
 * Record the post whose paywall is being built.
 *
 * Uses the post Beaver Builder itself would render the layout for (the main loop post, otherwise the global post)
 * rather than $post, because integrations like Sensei point $post at a different post while calling
 * memberful_wp_protect_content().
 *
 * @param mixed $content The marketing content being filtered.
 * @return mixed The content, unchanged.
 */
function memberful_wp_beaver_builder_remember_paywalled_post( $content ) {
  memberful_wp_beaver_builder_paywalled_post_ids( (int) FLBuilderModel::get_post_id( true ) );

  return $content;
}

/**
 * Keep Beaver Builder from rendering a layout in place of, or inside, Memberful paywall output.
 *
 * This is the filter Beaver Builder's own integrations use for the same purpose, see
 * FLBuilderCompatibility::wc_memberships_support(). It avoids unhooking FLBuilder::render_content from `the_content`,
 * which needed restoring from inside the same run and made WP_Hook skip the next priority bucket.
 *
 * @param mixed $do_render Whether Beaver Builder intends to render the layout, as filtered so far.
 * @param mixed $post_id   The post whose layout would render.
 * @return mixed
 */
function memberful_wp_beaver_builder_do_render_content( $do_render, $post_id ) {
  // Nested `the_content` runs while the paywall is being built, e.g. global snippets reading the post body.
  if ( doing_filter( 'memberful_wp_protect_content' ) || doing_filter( 'memberful_marketing_content' ) ) {
    return FALSE;
  }

  // The paywall already replaced this post's content earlier in the request, possibly from a `the_content`
  // callback that runs before Beaver Builder's, e.g. Sensei's at -10.
  if ( isset( memberful_wp_beaver_builder_paywalled_post_ids()[ (int) $post_id ] ) ) {
    return FALSE;
  }

  return $do_render;
}
