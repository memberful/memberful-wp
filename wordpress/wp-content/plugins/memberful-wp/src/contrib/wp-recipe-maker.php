<?php
/**
 * WP Recipe Maker integration.
 *
 * @package Memberful
 */

/**
 * Adds Memberful protection support for WP Recipe Maker recipe cards.
 */
class Memberful_Wp_Integration_WP_Recipe_Maker {
  /**
   * Post meta key used to store recipe card lock state.
   *
   * @var string
   */
  const LOCK_RECIPE_CARDS_META_KEY = 'memberful_wprm_lock_recipe_cards';

  /**
   * Class instance.
   *
   * @var Memberful_Wp_Integration_WP_Recipe_Maker|null
   */
  protected static ?Memberful_Wp_Integration_WP_Recipe_Maker $_instance = null;

  /**
   * Whether the recipe ID discovery filters should pass through unfiltered.
   *
   * @var bool
   */
  private bool $bypass_recipe_id_filters = false;

  /**
   * Get the class instance.
   *
   * @return Memberful_Wp_Integration_WP_Recipe_Maker The class instance.
   */
  public static function instance(): ?Memberful_Wp_Integration_WP_Recipe_Maker {
    if ( null === self::$_instance ) {
      self::$_instance = new self();
    }

    return self::$_instance;
  }

  /**
   * Constructor.
   */
  private function __construct() {
    $this->register_hooks();
  }

  /**
   * Register WP Recipe Maker and Memberful hooks.
   */
  private function register_hooks(): void {
    add_filter( 'wprm_recipe_shortcode_output', array( $this, 'filter_recipe_shortcode_output' ), 100, 2 );
    add_filter( 'wprm_recipe_snippet_shortcode_output', array( $this, 'filter_recipe_snippet_shortcode_output' ), 100, 3 );
    add_filter( 'render_block', array( $this, 'filter_rendered_block' ), 100, 2 );
    add_filter( 'do_shortcode_tag', array( $this, 'filter_shortcode_tag' ), 100, 3 );
    add_filter( 'wprm_get_recipe_ids_from_post', array( $this, 'filter_recipe_ids_from_post' ), 10, 2 );
    add_filter( 'wprm_get_recipe_ids_from_content', array( $this, 'filter_recipe_ids_from_content' ) );
    add_filter( 'wprm_recipes_on_page', array( $this, 'filter_recipes_on_page' ), 1000 );
    add_filter( 'wprm_recipe_metadata_cache_enabled', array( $this, 'filter_metadata_cache_enabled' ), 10, 2 );
    add_filter( 'wprm_recipe_metadata', array( $this, 'filter_recipe_metadata' ), 10, 2 );
    add_filter( 'wprm_recipe_frontend_data', array( $this, 'filter_recipe_frontend_data' ), 100, 2 );
    add_filter( 'wprm_print_output', array( $this, 'filter_print_output' ), 1000 );
    add_action( 'init', array( $this, 'register_rest_hooks' ) );
    add_filter( 'memberful_teaser_content', array( $this, 'add_locked_recipe_preview_to_teaser' ), 20, 2 );
    add_filter( 'memberful_wp_protect_content', array( $this, 'add_locked_recipe_preview_to_protected_content' ), 20 );
    add_filter( 'memberful_metabox_view_vars', array( $this, 'add_metabox_view_vars' ), 10, 2 );
    add_action( 'memberful_save_postdata', array( $this, 'save_recipe_card_lock' ) );
  }

  /**
   * Add WP Recipe Maker fields to the Memberful metabox view variables.
   *
   * @param array   $view_vars The metabox view variables.
   * @param WP_Post $post      The post being edited.
   * @return array The filtered metabox view variables.
   */
  public function add_metabox_view_vars( array $view_vars, WP_Post $post ): array {
    if ( $this->should_show_recipe_card_lock_option( $post ) ) {
      $view_vars['wprm_recipe_cards_locked'] = $this->recipe_cards_locked_for_post( $post->ID );
    }

    return $view_vars;
  }

  /**
   * Check whether the post should show the recipe card lock option.
   *
   * @param WP_Post|null $post The post being edited.
   * @return bool True when the lock option should be shown.
   */
  private function should_show_recipe_card_lock_option( ?WP_Post $post ): bool {
    if ( ! $post instanceof WP_Post ) {
      return false;
    }

    if ( defined( 'WPRM_POST_TYPE' ) && WPRM_POST_TYPE === $post->post_type ) {
      return false;
    }

    return true;
  }

  /**
   * Check whether recipe cards are locked for a post.
   *
   * @param int $post_id The post ID.
   * @return bool True when recipe cards are locked.
   */
  private function recipe_cards_locked_for_post( int $post_id ): bool {
    return get_post_meta( $post_id, self::LOCK_RECIPE_CARDS_META_KEY, true ) === '1';
  }

  /**
   * Save the recipe card lock option for a post.
   *
   * @param int $post_id The post ID.
   */
  public function save_recipe_card_lock( int $post_id ): void {
    $post = get_post( $post_id );

    if ( ! $this->should_show_recipe_card_lock_option( $post ) ) {
      return;
    }

    if ( ! isset( $_POST['memberful_wprm_lock_recipe_cards_present'] ) ) {
      return;
    }

    $is_locked = isset( $_POST['memberful_wprm_lock_recipe_cards'] ) && $_POST['memberful_wprm_lock_recipe_cards'] === '1';

    update_post_meta( $post_id, self::LOCK_RECIPE_CARDS_META_KEY, $is_locked ? '1' : '0' );
  }

  /**
   * Filter a full WP Recipe Maker recipe shortcode.
   *
   * @param string|mixed $output The shortcode output.
   * @param mixed        $recipe The WP Recipe Maker recipe object.
   * @return string|mixed The filtered shortcode output.
   */
  public function filter_recipe_shortcode_output( $output, $recipe ) {
    if ( ! is_string( $output ) || ! $this->should_lock_recipe_for_current_user( $recipe ) ) {
      return $output;
    }

    if ( $this->uses_legacy_recipe_templates() ) {
      return '';
    }

    return $this->prune_recipe_card_output( $output );
  }

  /**
   * Check whether WP Recipe Maker is rendering pre-4.0 legacy PHP templates.
   *
   * @return bool True when legacy templates are in use.
   */
  private function uses_legacy_recipe_templates(): bool {
    if ( ! class_exists( 'WPRM_Settings' ) ) {
      return false;
    }

    return 'legacy' === WPRM_Settings::get( 'recipe_template_mode' );
  }

  /**
   * Filter WP Recipe Maker snippet shortcode output.
   *
   * @param string|mixed $output    The shortcode output.
   * @param array|mixed  $atts      The shortcode attributes.
   * @param int|mixed    $recipe_id The recipe ID.
   * @return string|mixed The filtered shortcode output.
   */
  public function filter_recipe_snippet_shortcode_output( $output, $atts, $recipe_id ) {
    if ( ! is_string( $output ) ) {
      return $output;
    }

    $recipe_id = is_numeric( $recipe_id ) ? absint( $recipe_id ) : 0;

    if ( $this->should_lock_recipe_id_for_current_user( $recipe_id ) ) {
      return '';
    }

    return $output;
  }

  /**
   * Filter rendered WP Recipe Maker blocks.
   *
   * @param string|mixed $output The rendered block output.
   * @param mixed        $block  The parsed block data.
   * @return string|mixed The filtered block output.
   */
  public function filter_rendered_block( $output, $block ) {
    if ( ! is_string( $output ) || ! is_array( $block ) || empty( $block['blockName'] ) || ! is_string( $block['blockName'] ) || 0 !== strpos( $block['blockName'], 'wp-recipe-maker/' ) ) {
      return $output;
    }

    if ( ! $this->is_hidden_wprm_component_name( $block['blockName'] ) ) {
      return $output;
    }

    $recipe_id = empty( $block['attrs']['id'] ) || ! is_numeric( $block['attrs']['id'] ) ? 0 : absint( $block['attrs']['id'] );

    if ( $this->should_lock_recipe_id_for_current_user( $recipe_id ) ) {
      return '';
    }

    return $output;
  }

  /**
   * Filter rendered WP Recipe Maker shortcode tags.
   *
   * @param string|mixed $output The shortcode output.
   * @param string|mixed $tag    The shortcode tag.
   * @param array|mixed  $attr   The shortcode attributes.
   * @return string|mixed The filtered shortcode output.
   */
  public function filter_shortcode_tag( $output, $tag, $attr ) {
    if ( ! is_string( $output ) || ! is_string( $tag ) ) {
      return $output;
    }

    if ( 0 !== strpos( $tag, 'wprm-' ) || ! $this->is_hidden_wprm_component_name( $tag ) ) {
      return $output;
    }

    if ( $this->should_lock_recipe_id_for_current_user( $this->shortcode_recipe_id( $attr ) ) ) {
      return '';
    }

    return $output;
  }

  /**
   * Filter recipe IDs detected from a post.
   *
   * @param array|mixed $recipe_ids The detected recipe IDs.
   * @param int|mixed   $post_id    The post ID.
   * @return array|mixed The filtered recipe IDs.
   */
  public function filter_recipe_ids_from_post( $recipe_ids, $post_id ) {
    if ( ! is_array( $recipe_ids ) || $this->bypass_recipe_id_filters ) {
      return $recipe_ids;
    }

    if ( $this->should_lock_recipe_for_current_user( false, is_numeric( $post_id ) ? absint( $post_id ) : 0 ) ) {
      return array();
    }

    return $recipe_ids;
  }

  /**
   * Filter recipe IDs detected from content.
   *
   * @param array|mixed $recipe_ids The detected recipe IDs.
   * @return array|mixed The filtered recipe IDs.
   */
  public function filter_recipe_ids_from_content( $recipe_ids ) {
    if ( ! is_array( $recipe_ids ) || $this->bypass_recipe_id_filters ) {
      return $recipe_ids;
    }

    if ( $this->should_lock_recipe_for_current_user() ) {
      return array();
    }

    return $recipe_ids;
  }

  /**
   * Filter recipe IDs tracked on the current page.
   *
   * @param array|mixed $recipe_ids The recipe IDs on the page.
   * @return array|mixed The filtered recipe IDs.
   */
  public function filter_recipes_on_page( $recipe_ids ) {
    if ( ! is_array( $recipe_ids ) ) {
      return $recipe_ids;
    }

    $filtered_recipe_ids = array();

    foreach ( $recipe_ids as $recipe_id ) {
      if ( ! $this->should_lock_recipe_id_outside_loop( $recipe_id ) ) {
        $filtered_recipe_ids[] = $recipe_id;
      }
    }

    return $filtered_recipe_ids;
  }

  /**
   * Check a recipe lock where the loop's global $post is unreliable.
   *
   * Footer aggregates like WPRM's recipe JSON run after the loop, so the global $post is whichever post rendered last,
   * not the recipe's post.
   *
   * @param int $recipe_id The recipe ID.
   * @return bool True when the recipe should be locked for the current user.
   */
  private function should_lock_recipe_id_outside_loop( int $recipe_id ): bool {
    $queried_post_id = $this->queried_non_recipe_post_id();

    if ( $queried_post_id ) {
      return $this->should_lock_recipe_id_for_current_user( $recipe_id, $queried_post_id );
    }

    $parent_post_id = $this->recipe_parent_post_id( $this->recipe_for_id( absint( $recipe_id ) ) );

    if ( ! $parent_post_id ) {
      return false;
    }

    return $this->should_lock_recipe_id_for_current_user( $recipe_id, $parent_post_id );
  }

  /**
   * Disable metadata caching for recipes with Memberful-dependent visibility.
   *
   * The wprm_recipe_metadata filter only runs on cache misses, so caching must stay off for protected recipes or
   * filter_recipe_metadata gets skipped.
   *
   * @param bool|mixed $enabled Whether metadata caching is enabled.
   * @param mixed      $recipe  The WP Recipe Maker recipe object.
   * @return bool|mixed Whether metadata caching should remain enabled.
   */
  public function filter_metadata_cache_enabled( $enabled, $recipe ) {
    if ( $this->recipe_has_memberful_protection( $recipe ) ) {
      return false;
    }

    return $enabled;
  }

  /**
   * Filter recipe metadata for locked recipes.
   *
   * @param array|mixed $metadata The recipe metadata.
   * @param mixed       $recipe   The WP Recipe Maker recipe object.
   * @return array|mixed The filtered recipe metadata.
   */
  public function filter_recipe_metadata( $metadata, $recipe ) {
    if ( is_array( $metadata ) && $this->should_lock_recipe_for_current_user( $recipe ) ) {
      return array();
    }

    return $metadata;
  }

  /**
   * Strip locked ingredients from the recipe data WPRM hands to its scripts.
   *
   * Covers the footer recipe JSON and the public manage/recipe REST route WPRM's script falls back to when a recipe
   * is missing from that JSON. Both run without a reliable loop context, so the lock resolves the same way as the
   * footer recipe list.
   *
   * @param array|mixed $data   The frontend recipe data.
   * @param mixed       $recipe The WP Recipe Maker recipe object.
   * @return array|mixed The filtered frontend recipe data.
   */
  public function filter_recipe_frontend_data( $data, $recipe ) {
    if ( ! is_array( $data ) || ! is_object( $recipe ) || ! method_exists( $recipe, 'id' ) ) {
      return $data;
    }

    if ( $this->should_lock_recipe_id_outside_loop( absint( $recipe->id() ) ) ) {
      $data['ingredients'] = array();
    }

    return $data;
  }

  /**
   * Filter WP Recipe Maker print output for locked recipes.
   *
   * @param array|false $output The print output data.
   * @return array|false The filtered print output data.
   */
  public function filter_print_output( $output ) {
    if ( ! $output || current_user_can( 'publish_posts' ) || empty( $output['recipe_ids'] ) || ! is_array( $output['recipe_ids'] ) ) {
      return $output;
    }

    foreach ( $output['recipe_ids'] as $recipe_id ) {
      if ( $this->should_lock_recipe_id_for_current_user( $recipe_id ) ) {
        return false;
      }
    }

    return $output;
  }

  /**
   * Register REST hooks once the WPRM post type constant is available.
   */
  public function register_rest_hooks(): void {
    if ( ! defined( 'WPRM_POST_TYPE' ) ) {
      return;
    }

    add_filter( 'rest_prepare_' . WPRM_POST_TYPE, array( $this, 'filter_rest_prepare_recipe' ), 100, 2 );
  }

  /**
   * Strip protected recipe data from public REST responses.
   *
   * WPRM force-enables show_in_rest for recipes, so anonymous requests can
   * read full recipe data and the fallback content unless removed here.
   *
   * @param WP_REST_Response|mixed $response The response object.
   * @param WP_Post|mixed          $post     The recipe post.
   * @return WP_REST_Response|mixed The filtered response.
   */
  public function filter_rest_prepare_recipe( $response, $post ) {
    if ( ! $response instanceof WP_REST_Response || ! $post instanceof WP_Post ) {
      return $response;
    }

    if ( ! $this->should_lock_recipe_id_for_current_user( $post->ID ) ) {
      return $response;
    }

    $data = $response->get_data();

    if ( ! is_array( $data ) ) {
      return $response;
    }

    if ( array_key_exists( 'recipe', $data ) ) {
      $data['recipe'] = null;
    }

    if ( isset( $data['content'] ) && is_array( $data['content'] ) ) {
      $data['content']['rendered'] = '';
    }

    // Ingredient and equipment terms resolve to names through the public term endpoints.
    foreach ( array( 'wprm_ingredient', 'wprm_equipment' ) as $taxonomy ) {
      if ( isset( $data[ $taxonomy ] ) ) {
        $data[ $taxonomy ] = array();
      }
    }

    $response->set_data( $data );

    return $response;
  }

  /**
   * Add locked recipe previews to Memberful teaser content.
   *
   * @param string       $content The teaser content.
   * @param WP_Post|null $post    The protected post.
   * @return string The teaser content with recipe previews.
   */
  public function add_locked_recipe_preview_to_teaser( string $content, ?WP_Post $post ): string {
    if ( ! $post instanceof WP_Post ) {
      return $content;
    }

    if ( false !== strpos( $content, 'wprm-recipe-container' ) ) {
      return $content;
    }

    $preview = $this->locked_recipe_preview_for_post( $post );

    if ( '' === trim( $preview ) ) {
      return $content;
    }

    return $content . $preview;
  }

  /**
   * Add locked recipe previews to protected post marketing content.
   *
   * @param string $content The protected marketing content.
   * @return string The filtered protected marketing content.
   */
  public function add_locked_recipe_preview_to_protected_content( string $content ): string {
    static $adding_preview = false;
    global $post;

    if ( $adding_preview || ! $post instanceof WP_Post || memberful_can_user_access_post( get_current_user_id(), $post->ID ) ) {
      return $content;
    }

    // Divider posts get their preview through the memberful_teaser_content filter.
    if ( has_block( 'memberful/paywall-divider', $post ) ) {
      return $content;
    }

    if ( false !== strpos( $content, 'wprm-recipe-container' ) ) {
      return $content;
    }

    $adding_preview = true;
    $preview = $this->locked_recipe_preview_for_post( $post );
    $adding_preview = false;

    if ( '' === trim( $preview ) ) {
      return $content;
    }

    if ( preg_match( '/<div class=["\']memberful-global-marketing-content["\']>/', $content, $matches, PREG_OFFSET_CAPTURE ) ) {
      return substr_replace( $content, $preview, $matches[0][1], 0 );
    }

    return $preview . $content;
  }

  /**
   * Get WPRM component names that stay visible when a recipe is locked.
   *
   * @return array Component names.
   */
  private function teaser_visible_wprm_components(): array {
    return apply_filters( 'memberful_wprm_teaser_visible_components', array(
      // Blocks that only render layout, links, or teaser-level data.
      'wp-recipe-maker/jump-to-recipe',
      'wp-recipe-maker/list',
      'wp-recipe-maker/recipe-part', // Renders a wprm-{part} shortcode, filtered below.
      'wp-recipe-maker/recipe-roundup-item',
      // Identity and teaser data.
      'wprm-recipe-name',
      'wprm-recipe-image',
      'wprm-recipe-summary',
      'wprm-recipe-author',
      'wprm-recipe-author-bio',
      'wprm-recipe-author-container',
      'wprm-recipe-date',
      'wprm-recipe-rating',
      'wprm-recipe-servings',
      'wprm-recipe-servings-unit',
      'wprm-recipe-servings-container',
      'wprm-recipe-time',
      'wprm-recipe-tag',
      'wprm-recipe-tag-container',
      'wprm-recipe-tags-container',
      'wprm-recipe-meta-container',
      // Navigation. Section jumps are excluded: their anchors target pruned sections.
      'wprm-recipe-jump',
      'wprm-recipe-jump-to-comments',
      // Sharing.
      'wprm-recipe-pin',
      'wprm-recipe-facebook-share',
      'wprm-recipe-twitter-share',
      'wprm-recipe-bluesky-share',
      'wprm-recipe-mastodon-share',
      'wprm-recipe-messenger-share',
      'wprm-recipe-email-share',
      'wprm-recipe-text-share',
      'wprm-recipe-tumblr-share',
      'wprm-recipe-share-options-popup',
      // Structure and marketing.
      'wprm-icon',
      'wprm-image',
      'wprm-link',
      'wprm-expandable',
      'wprm-call-to-action',
      // Roundup and list placements (shortcode forms of the blocks above).
      'wprm-list',
      'wprm-recipe-roundup-item',
      'wprm-recipe-roundup-link',
      'wprm-recipe-roundup-credit',
    ) );
  }

  /**
   * Check whether a WPRM component should be hidden when locked.
   *
   * @param string $name Block name or shortcode tag.
   * @return bool True when the component should be hidden.
   */
  private function is_hidden_wprm_component_name( string $name ): bool {
    // The full recipe card stays visible and is pruned down to a preview instead.
    if ( 'wp-recipe-maker/recipe' === $name || 'wprm-recipe' === $name ) {
      return false;
    }

    return ! in_array( $name, $this->teaser_visible_wprm_components(), true );
  }

  /**
   * Determine whether a recipe has any Memberful-dependent visibility.
   *
   * @param mixed $recipe The WP Recipe Maker recipe object.
   * @return bool True when the recipe should avoid shared WPRM caches.
   */
  private function recipe_has_memberful_protection( $recipe = false ): bool {
    $lock_post_id = $this->lock_post_id_for_recipe( $recipe );

    if ( ! $lock_post_id ) {
      return false;
    }

    return $this->recipe_cards_locked_for_post( $lock_post_id ) && ! memberful_can_user_access_post( 0, $lock_post_id );
  }

  /**
   * Check whether the current user should see locked output for a recipe.
   *
   * @param mixed $recipe  The WP Recipe Maker recipe object.
   * @param int   $post_id Optional explicit post ID.
   * @return bool True when the recipe should be locked for the current user.
   */
  private function should_lock_recipe_for_current_user( $recipe = false, int $post_id = 0 ): bool {
    if ( current_user_can( 'publish_posts' ) ) {
      return false;
    }

    $lock_post_id = $this->lock_post_id_for_recipe( $recipe, $post_id );

    if ( ! $lock_post_id ) {
      return false;
    }

    // A metered sample view releases the whole post body, recipe cards included.
    if ( function_exists( 'memberful_metering_is_releasing' ) && memberful_metering_is_releasing( $lock_post_id ) ) {
      return false;
    }

    return $this->recipe_cards_locked_for_post( $lock_post_id ) && ! memberful_can_user_access_post( get_current_user_id(), $lock_post_id );
  }

  /**
   * Check whether the current user should see locked output for a recipe ID.
   *
   * @param int $recipe_id The recipe ID.
   * @param int $post_id   Optional explicit post ID.
   * @return bool True when the recipe should be locked for the current user.
   */
  private function should_lock_recipe_id_for_current_user( int $recipe_id, int $post_id = 0 ): bool {
    return $this->should_lock_recipe_for_current_user( $this->recipe_for_id( absint( $recipe_id ) ), $post_id );
  }

  /**
   * Get the current post ID used to evaluate recipe card locking.
   *
   * @return int The context post ID, or 0 when unavailable.
   */
  private function context_post_id(): int {
    global $post;

    if ( $post instanceof WP_Post && ( ! defined( 'WPRM_POST_TYPE' ) || WPRM_POST_TYPE !== $post->post_type ) ) {
      return $post->ID;
    }

    return $this->queried_non_recipe_post_id();
  }

  /**
   * Get the queried singular post ID unless it is a WPRM recipe.
   *
   * Recipes viewed at their own public CPT URL must resolve locking against the recipe's parent post, where the lock
   * meta lives.
   *
   * @return int The queried post ID, or 0.
   */
  private function queried_non_recipe_post_id(): int {
    if ( ! is_singular() ) {
      return 0;
    }

    $post_id = absint( get_queried_object_id() );

    if ( ! $post_id || ( defined( 'WPRM_POST_TYPE' ) && WPRM_POST_TYPE === get_post_type( $post_id ) ) ) {
      return 0;
    }

    return $post_id;
  }

  /**
   * Get the parent post ID for a recipe.
   *
   * @param mixed $recipe The WP Recipe Maker recipe object.
   * @return int The parent post ID, or 0 when unavailable.
   */
  private function recipe_parent_post_id( $recipe ): int {
    if ( is_object( $recipe ) && method_exists( $recipe, 'parent_post_id' ) ) {
      return absint( $recipe->parent_post_id() );
    }

    return 0;
  }

  /**
   * Get a WP Recipe Maker recipe object by ID.
   *
   * @param int $recipe_id The recipe ID.
   * @return mixed|false The recipe object, or false when unavailable.
   */
  private function recipe_for_id( int $recipe_id ) {
    if ( ! $recipe_id || ! class_exists( 'WPRM_Recipe_Manager' ) ) {
      return false;
    }

    return WPRM_Recipe_Manager::get_recipe( $recipe_id );
  }

  /**
   * Determine which post controls locking for a recipe.
   *
   * Requests without an embedding post context (print URLs, REST) resolve via
   * the recipe's WPRM parent post; reuse in other posts is not scanned.
   *
   * @param mixed $recipe  The WP Recipe Maker recipe object.
   * @param int   $post_id Optional explicit post ID.
   * @return int The post ID used for lock checks.
   */
  private function lock_post_id_for_recipe( $recipe = false, int $post_id = 0 ): int {
    if ( $post_id ) {
      return absint( $post_id );
    }

    $context_post_id = $this->context_post_id();

    if ( $context_post_id ) {
      return $context_post_id;
    }

    return $this->recipe_parent_post_id( $recipe );
  }

  /**
   * Remove locked details from full WP Recipe Maker card output.
   *
   * @param string $output The full recipe card HTML.
   * @return string The filtered recipe card HTML.
   */
  private function prune_recipe_card_output( string $output ): string {
    if ( ! class_exists( 'DOMDocument' ) || '' === trim( $output ) ) {
      return '';
    }

    $document = new DOMDocument();
    $previous_errors = libxml_use_internal_errors( true );
    $loaded = $document->loadHTML(
      '<?xml encoding="UTF-8"><div id="memberful-wprm-fragment">' . $output . '</div>',
      LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
    );
    libxml_clear_errors();
    libxml_use_internal_errors( $previous_errors );

    if ( ! $loaded ) {
      return '';
    }

    $xpath = new DOMXPath( $document );

    foreach ( $xpath->query( '//script[contains(translate(@type, "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz"), "application/ld+json")]' ) as $node ) {
      $node->parentNode->removeChild( $node );
    }

    $this->remove_empty_template_wrappers( $xpath );

    $fragment = $document->getElementById( 'memberful-wprm-fragment' );

    if ( ! $fragment ) {
      return '';
    }

    $html = '';

    foreach ( $fragment->childNodes as $child ) {
      $html .= $document->saveHTML( $child );
    }

    return $html;
  }

  /**
   * Remove WPRM template wrappers that no longer contain visible content.
   *
   * @param DOMXPath $xpath The DOM XPath instance.
   */
  private function remove_empty_template_wrappers( DOMXPath $xpath ) {
    do {
      $removed = false;
      $nodes = array_reverse( $this->empty_template_wrapper_candidates( $xpath ) );

      foreach ( $nodes as $node ) {
        if ( ! $node->parentNode || $this->node_has_visible_content( $node ) ) {
          continue;
        }

        $node->parentNode->removeChild( $node );
        $removed = true;
      }
    } while ( $removed );
  }

  /**
   * Find WPRM template wrapper elements that can be removed when empty.
   *
   * @param DOMXPath $xpath The DOM XPath instance.
   * @return array Candidate wrapper nodes.
   */
  private function empty_template_wrapper_candidates( DOMXPath $xpath ): array {
    $query = '//*[
      contains(concat(" ", normalize-space(@class), " "), " wprm-layout-container ")
      or contains(concat(" ", normalize-space(@class), " "), " wprm-layout-column-container ")
      or contains(concat(" ", normalize-space(@class), " "), " wprm-layout-column ")
      or contains(concat(" ", normalize-space(@class), " "), " wprm-container-")
      or contains(concat(" ", normalize-space(@class), " "), " wprm-template-")
    ]';
    $nodes = array();

    foreach ( $xpath->query( $query ) as $node ) {
      $nodes[] = $node;
    }

    return $nodes;
  }

  /**
   * Determine whether a node contains visible preview content.
   *
   * @param DOMNode $node The node to inspect.
   * @return bool Whether the node contains visible content.
   */
  private function node_has_visible_content( DOMNode $node ): bool {
    if ( XML_TEXT_NODE === $node->nodeType ) {
      return '' !== trim( $node->textContent );
    }

    if ( XML_ELEMENT_NODE !== $node->nodeType ) {
      return false;
    }

    $visible_tags = array( 'img', 'picture', 'svg', 'video', 'audio', 'iframe', 'canvas' );

    if ( in_array( strtolower( $node->nodeName ), $visible_tags, true ) ) {
      return true;
    }

    foreach ( $node->childNodes as $child ) {
      if ( $this->node_has_visible_content( $child ) ) {
        return true;
      }
    }

    return false;
  }

  /**
   * Render locked recipe cards for a protected post.
   *
   * @param WP_Post|null $post The protected post.
   * @return string The recipe preview HTML.
   */
  private function locked_recipe_preview_for_post( ?WP_Post $post ): string {
    if ( ! $post instanceof WP_Post ) {
      return '';
    }

    $output = '';

    foreach ( $this->recipe_ids_in_content( $post->post_content ) as $recipe_id ) {
      if ( ! $this->should_lock_recipe_id_for_current_user( $recipe_id, $post->ID ) ) {
        continue;
      }

      // The canonical WPRM render path; filter_recipe_shortcode_output prunes it.
      $output .= do_shortcode( '[wprm-recipe id="' . $recipe_id . '"]' );
    }

    return $output;
  }

  /**
   * Find recipe IDs in content using WPRM's own discovery.
   *
   * Uses get_recipe_ids_from_content because get_recipe_ids_from_post caches its result after our own filters have
   * already emptied it for locked posts.
   *
   * @param string $content The post content.
   * @return array Recipe IDs in content order.
   */
  private function recipe_ids_in_content( string $content ): array {
    if ( ! class_exists( 'WPRM_Recipe_Manager' ) ) {
      return array();
    }

    $this->bypass_recipe_id_filters = true;
    $recipe_ids = WPRM_Recipe_Manager::get_recipe_ids_from_content( $content );
    $this->bypass_recipe_id_filters = false;

    if ( ! is_array( $recipe_ids ) ) {
      return array();
    }

    return array_unique( array_map( 'absint', $recipe_ids ) );
  }

  /**
   * Get the recipe ID for a rendered shortcode.
   *
   * @param array|mixed $attr The shortcode attributes.
   * @return int The recipe ID.
   */
  private function shortcode_recipe_id( $attr ): int {
    if ( is_array( $attr ) && ! empty( $attr['id'] ) ) {
      return absint( $attr['id'] );
    }

    if ( class_exists( 'WPRM_Template_Shortcodes' ) && method_exists( 'WPRM_Template_Shortcodes', 'get_current_recipe_id' ) ) {
      return absint( WPRM_Template_Shortcodes::get_current_recipe_id() );
    }

    return 0;
  }
}

Memberful_Wp_Integration_WP_Recipe_Maker::instance();
