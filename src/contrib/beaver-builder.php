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
 * Beaver Builder only renders the layout inside the loop, so generated excerpts, REST responses, and the global snippets
 * teaser would serve that fallback through `the_content`. For layouts with a node hidden from the current viewer, those
 * get the real layout, or nothing, instead. Code reading `post_content` directly still sees the fallback.
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
  add_filter( 'the_content', 'memberful_wp_beaver_builder_filter_fallback_content' );
  add_filter( 'get_the_excerpt', 'memberful_wp_beaver_builder_start_excerpt', 9, 2 );
  add_filter( 'get_the_excerpt', 'memberful_wp_beaver_builder_end_excerpt', 11 );

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
  if ( ! doing_filter( 'the_content' ) ) {
    return $content;
  }

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
  if ( memberful_wp_beaver_builder_building_paywall() ) {
    return FALSE;
  }

  // The paywall already replaced this post's content earlier in the request, possibly from a `the_content`
  // callback that runs before Beaver Builder's, e.g. Sensei's at -10.
  if ( isset( memberful_wp_beaver_builder_paywalled_post_ids()[ (int) $post_id ] ) ) {
    return FALSE;
  }

  return $do_render;
}

/**
 * Track the post whose excerpt is being generated.
 *
 * The excerpt's post needn't be the global post: wp_trim_excerpt() runs `the_content` for the post it was given,
 * e.g. get_the_excerpt( $id ) in a related-posts widget.
 *
 * @param mixed $excerpt The excerpt, as filtered so far.
 * @param mixed $post    The post the excerpt belongs to.
 * @return mixed The excerpt, unchanged.
 */
function memberful_wp_beaver_builder_start_excerpt( $excerpt, $post = null ) {
  $post = get_post( $post );

  memberful_wp_beaver_builder_excerpt_post_ids( 'push', $post ? $post->ID : 0 );

  return $excerpt;
}

/**
 * Stop tracking the post whose excerpt was generated.
 *
 * @param mixed $excerpt The generated excerpt.
 * @return mixed The excerpt, unchanged.
 */
function memberful_wp_beaver_builder_end_excerpt( $excerpt ) {
  memberful_wp_beaver_builder_excerpt_post_ids( 'pop' );

  return $excerpt;
}

/**
 * The stack of posts whose excerpts are being generated, innermost first.
 *
 * @param string $action  "push", "pop", or "get".
 * @param int    $post_id The post ID to push.
 * @return int[]
 */
function memberful_wp_beaver_builder_excerpt_post_ids( string $action = 'get', int $post_id = 0 ): array {
  static $ids = array();

  if ( 'push' === $action ) {
    array_unshift( $ids, $post_id );
  } elseif ( 'pop' === $action ) {
    array_shift( $ids );
  }

  return $ids;
}

/**
 * Keep restricted modules in Beaver Builder's plain-text fallback away from viewers they're hidden from.
 *
 * Beaver Builder only renders the layout inside the loop, so `the_content` gets the fallback saved to `post_content`,
 * restricted modules included, in three places this filter handles:
 *
 * - Generated excerpts (og:description, related posts widgets), for the post the excerpt belongs to.
 * - REST requests, for the post being prepared, which the controller sets as the global post.
 * - The global snippets teaser, read while the paywall is being built. Beaver Builder rendering is off there, so
 *   layouts with a hidden node get no teaser at all.
 *
 * Anywhere else `the_content` may be filtering text that isn't the post's content, e.g. an author bio, so it's left
 * alone. In these places the real layout is rendered instead, or nothing when Beaver Builder rendering is turned off
 * for the post: while the paywall is being built, after Sensei's lesson paywall, by WooCommerce Memberships, or inside
 * another layout's render. Runs after FLBuilder::render_content at the same priority.
 *
 * @param mixed $content The content, as filtered so far.
 * @return mixed The content.
 */
function memberful_wp_beaver_builder_filter_fallback_content( $content ) {
  static $rendering = array();

  $excerpt_post_ids = memberful_wp_beaver_builder_excerpt_post_ids();
  $building_paywall = memberful_wp_beaver_builder_building_paywall();
  $rest_request     = defined( 'REST_REQUEST' ) && REST_REQUEST;

  if ( ! $excerpt_post_ids && ! $building_paywall && ! $rest_request ) {
    return $content;
  }

  $global_post_id = (int) FLBuilderModel::get_post_id( true );
  $post_id        = $excerpt_post_ids ? $excerpt_post_ids[0] : $global_post_id;

  if ( ! $post_id || isset( $rendering[ $post_id ] ) || FLBuilder::$post_rendering === $post_id ) {
    return $content;
  }

  if ( ! FLBuilderModel::is_builder_enabled( $post_id ) || ! memberful_wp_beaver_builder_layout_hides_nodes( $post_id ) ) {
    return $content;
  }

  if ( $building_paywall ) {
    return '';
  }

  $do_render = apply_filters( 'fl_builder_do_render_content', true, $post_id );

  // Beaver Builder rendered the layout itself, applying node visibility. It only renders the global post.
  $rendered_by_beaver_builder = $post_id === $global_post_id
    && ( in_the_loop() || in_array( $post_id, array_map( 'intval', (array) FLBuilderModel::get_global_posts() ), true ) );

  if ( $do_render && $rendered_by_beaver_builder ) {
    return $content;
  }

  // Rendering here would undo the reason it's off; inside another layout's render, the inner render's cleanup would
  // also turn rendering back on for the rest of the outer one.
  if ( ! $do_render ) {
    return '';
  }

  $rendering[ $post_id ] = true;

  ob_start();
  FLBuilder::render_content_by_id( $post_id );
  $layout = ob_get_clean();

  unset( $rendering[ $post_id ] );

  return $layout;
}

/**
 * Whether a Memberful paywall is being built, which runs nested `the_content` calls, e.g. global snippets reading the
 * post body.
 *
 * @return bool
 */
function memberful_wp_beaver_builder_building_paywall(): bool {
  return doing_filter( 'memberful_wp_protect_content' ) || doing_filter( 'memberful_marketing_content' );
}

/**
 * Whether the post's published layout has a row, column, or module the current viewer can't see.
 *
 * Nodes nested inside a hidden node don't need checking: the hidden ancestor already makes this true.
 *
 * @param int $post_id The post ID.
 * @return bool
 */
function memberful_wp_beaver_builder_layout_hides_nodes( int $post_id ): bool {
  foreach ( (array) FLBuilderModel::get_layout_data( 'published', $post_id ) as $node ) {
    if ( is_object( $node ) && isset( $node->settings ) && is_object( $node->settings ) && ! memberful_wp_beaver_builder_is_node_visible( true, $node ) ) {
      return true;
    }
  }

  return false;
}
