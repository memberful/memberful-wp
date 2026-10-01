<?php
/**
 * Metering runtime: per-request decision computation, caching, and rule matching.
 *
 * @package memberful-wp
 */

/**
 * Class Memberful_Metering_Access.
 */
class Memberful_Metering_Access {
  const DECISION_IGNORE       = 'ignore';
  const DECISION_ALLOW_SAMPLE = 'allow_sample';
  const DECISION_TRIP_METER   = 'trip_meter';

  const RENDER_NONE             = 'none';
  const RENDER_FREE_METER       = 'free_meter';

  /**
   * Per-request decision cache keyed by post ID.
   *
   * @var array<int, array{decision: string, remaining: int}>
   */
  private static $decisions = array();

  /**
   * Anonymous render mode for the singular post under view (one per request), for the render/enqueue layer.
   *
   * @var array{post_id: int, mode: string}
   */
  private static $anon_render = array(
    'post_id' => 0,
    'mode'    => self::RENDER_NONE,
  );

  /**
   * Register hooks. Called once from src/metering.php.
   */
  public static function register(): void {
    add_action( 'template_redirect', array( __CLASS__, 'on_template_redirect' ) );
    add_filter( 'memberful_paywall_free_view_limit', array( __CLASS__, 'filter_paywall_free_view_limit' ) );
  }

  /**
   * Compute the metering outcome for the singular post under view.
   *
   * Logged-in visitors are decided server-side here (their page is never edge-cached). Anonymous visitors only get a
   * count-agnostic render mode cached for the render layer; their per-visitor enforcement happens client-side
   * (localStorage), so the page stays cacheable even on hosts that strip cookies before PHP.
   */
  public static function on_template_redirect(): void {
    if ( ! self::is_metered_request() ) {
      return;
    }

    $config = Memberful_Metering_Config::get();
    if ( empty( $config['enabled'] ) || empty( $config['rules'] ) ) {
      return;
    }

    $post = get_queried_object();
    if ( ! ( $post instanceof WP_Post ) ) {
      return;
    }

    $user_id = get_current_user_id();
    $mode    = self::classify_post( $post, $user_id, $config );

    if ( self::RENDER_NONE === $mode ) {
      return;
    }

    if ( $user_id ) {
      // Browsers drop a failed prefetch and load the page normally on click, which is then counted. Serving the
      // article uncounted instead would let a forged prefetch header read every post for free.
      if ( self::is_prefetch_request() ) {
        self::emit_no_cache_headers();
        status_header( 503 );
        exit;
      }

      $result = self::record_and_check( $user_id, $post->ID, (int) $config['period_days'], (int) $config['registered_limit'] );

      self::emit_no_cache_headers();
      self::cache(
        $post->ID,
        $result['allowed'] ? self::DECISION_ALLOW_SAMPLE : self::DECISION_TRIP_METER,
        $result['remaining']
      );
      return;
    }

    self::$anon_render = array(
      'post_id' => (int) $post->ID,
      'mode'    => $mode,
    );
  }

  /**
   * Classify a post for metering independent of any view count, so the result is identical for every anonymous
   * visitor and safe to cache.
   *
   * @param WP_Post $post    Post under consideration.
   * @param int     $user_id Current user ID (0 for anonymous).
   * @param array   $config  Sanitised metering config.
   *
   * @return string One of the RENDER_* constants.
   */
  public static function classify_post( WP_Post $post, int $user_id, array $config ): string {
    if ( empty( $config['enabled'] ) || empty( $config['rules'] ) ) {
      return self::RENDER_NONE;
    }

    // Without the global paywall there is nothing to show once the meter trips - the post would render blank - so
    // treat an unconfigured paywall as "not metered". The Metering screen warns about this state.
    if ( ! get_option( 'memberful_use_global_marketing' ) ) {
      return self::RENDER_NONE;
    }

    if ( get_post_meta( $post->ID, 'memberful_metering_exempt', true ) ) {
      return self::RENDER_NONE;
    }

    // A WordPress password-protected post is gated by its password form, not by the meter. Metering it would let the
    // paywall's global snippet render the raw body for visitors who never entered the password.
    if ( post_password_required( $post ) ) {
      return self::RENDER_NONE;
    }

    if (
      ! self::post_matches_any_group( $post, $config['rules'] )
      || self::post_matches_any_group( $post, $config['exclude_rules'] )
    ) {
      return self::RENDER_NONE;
    }

    $has_plan_or_download = $user_id && (
      ! empty( memberful_wp_user_plans_subscribed_to( $user_id ) )
      || ! empty( memberful_wp_user_downloads( $user_id ) )
    );
    if ( $has_plan_or_download ) {
      return self::RENDER_NONE;
    }

    // Members-only posts are never metered: the access rules show the paywall to anyone not entitled to them.
    if ( ! memberful_can_user_access_post( 0, $post->ID ) ) {
      return self::RENDER_NONE;
    }

    return self::RENDER_FREE_METER;
  }

  /**
   * Record a logged-in visitor's view (when not already counted) and report whether it is allowed plus how many remain
   * in the window.
   *
   * @param int $user_id     User ID.
   * @param int $post_id     Post being viewed.
   * @param int $period_days Rolling-window length in days.
   * @param int $limit       Views allowed in the window.
   *
   * @return array{allowed: bool, remaining: int, persisted: bool, recorded: bool}
   */
  public static function record_and_check( int $user_id, int $post_id, int $period_days, int $limit ): array {
    $limit = max( 0, $limit );

    if ( 0 === $limit ) {
      return array(
        'allowed'   => false,
        'remaining' => 0,
        'persisted' => true,
        'recorded'  => false,
      );
    }

    $views = Memberful_Metering_Storage::read_user_views( $user_id );
    $views = Memberful_Metering_Storage::prune( $views, $period_days );

    $already_counted = isset( $views[ $post_id ] );

    if ( ! $already_counted && count( $views ) >= $limit ) {
      return array(
        'allowed'   => false,
        'remaining' => 0,
        'persisted' => true,
        'recorded'  => false,
      );
    }

    if ( ! $already_counted ) {
      $views[ $post_id ] = time();

      if ( ! Memberful_Metering_Storage::write_user_views( $user_id, $views ) ) {
        return array(
          'allowed'   => false,
          'remaining' => 0,
          'persisted' => false,
          'recorded'  => false,
        );
      }
    }

    return array(
      'allowed'   => true,
      'remaining' => max( 0, $limit - count( $views ) ),
      'persisted' => true,
      'recorded'  => ! $already_counted,
    );
  }

  /**
   * Return the cached decision for a post in the current request.
   *
   * @param int $post_id Post ID.
   *
   * @return string One of the DECISION_* constants. Defaults to DECISION_IGNORE.
   */
  public static function get_current_decision( int $post_id ): string {
    return isset( self::$decisions[ $post_id ]['decision'] )
      ? self::$decisions[ $post_id ]['decision']
      : self::DECISION_IGNORE;
  }

  /**
   * Return the remaining-views count for a post in the current request.
   *
   * @param int $post_id Post ID.
   *
   * @return int Number of views remaining in the period. Zero when the meter has tripped.
   */
  public static function get_current_remaining( int $post_id ): int {
    return isset( self::$decisions[ $post_id ]['remaining'] )
      ? (int) self::$decisions[ $post_id ]['remaining']
      : 0;
  }

  /**
   * Supply the free-view limit shown in the paywall counter when the meter blocked the post under view.
   *
   * A logged-in visitor's paywall renders only once the server has tripped the meter, so their registered limit is
   * returned then. An anonymous free-meter page ships its paywall hidden and only reveals it when the client meter
   * trips, so the anonymous limit is returned for that render mode; it is seen by exactly the blocked visitors while
   * the page stays byte-identical for caching.
   *
   * Leaves the incoming value untouched for every other paywall render (members-only posts, non-metered posts, the
   * admin preview), so the counter stays hidden there. Untyped on purpose: this is a public filter and an earlier
   * third-party callback may hand over anything; the renderer normalises the final value.
   *
   * @param int|null $limit Incoming limit. Null hides the counter.
   *
   * @return int|null
   */
  public static function filter_paywall_free_view_limit( $limit ) {
    $post_id = (int) get_queried_object_id();
    if ( ! $post_id ) {
      return $limit;
    }

    $config = Memberful_Metering_Config::get();

    if ( self::RENDER_FREE_METER === self::current_anon_mode( $post_id ) ) {
      return (int) $config['anonymous_limit'];
    }

    if ( self::DECISION_TRIP_METER === self::get_current_decision( $post_id ) ) {
      return (int) $config['registered_limit'];
    }

    return $limit;
  }

  /**
   * The anonymous render mode cached for a post in this request, or RENDER_NONE.
   *
   * @param int $post_id Post ID.
   *
   * @return string
   */
  public static function current_anon_mode( int $post_id ): string {
    return self::$anon_render['post_id'] === $post_id ? self::$anon_render['mode'] : self::RENDER_NONE;
  }

  /**
   * Whether the singular post under view is an anonymous metered view.
   *
   * @return bool
   */
  public static function is_anon_metered_view(): bool {
    return self::RENDER_NONE !== self::current_anon_mode( (int) get_queried_object_id() );
  }

  /**
   * Store a decision in the per-request cache.
   *
   * @param int    $post_id   Post ID.
   * @param string $decision  Decision constant.
   * @param int    $remaining Remaining views in the period (default 0).
   */
  private static function cache( int $post_id, string $decision, int $remaining = 0 ): void {
    self::$decisions[ $post_id ] = array(
      'decision'  => $decision,
      'remaining' => $remaining,
    );
  }

  /**
   * Disable page caching for this response.
   */
  private static function emit_no_cache_headers(): void {
    nocache_headers();

    // WordPress before 6.8 leaves no-store and private out of Cache-Control for logged-out visitors. A metered page
    // (or a rejected prefetch) must never be reused from any cache, so send the full directive set explicitly.
    header( 'Cache-Control: no-cache, must-revalidate, max-age=0, no-store, private' );

    if ( ! defined( 'DONOTCACHEPAGE' ) ) {
      define( 'DONOTCACHEPAGE', true );
    }
  }

  /**
   * Whether this request is eligible for metering: a front-end GET view of singular content, and never for site
   * contributors (anyone who can edit posts, including a post's own author), who see content in full and so are not
   * metering targets.
   *
   * @return bool
   */
  private static function is_metered_request(): bool {
    if ( is_admin() || is_feed() || is_preview() || is_embed() || wp_doing_cron() || wp_doing_ajax() || ! is_singular() ) {
      return false;
    }

    if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
      return false;
    }

    $method = isset( $_SERVER['REQUEST_METHOD'] )
      ? strtoupper( (string) wp_unslash( $_SERVER['REQUEST_METHOD'] ) )
      : '';

    if ( 'GET' !== $method || current_user_can( 'edit_posts' ) ) {
      return false;
    }

    return true;
  }

  /**
   * Whether the browser fetched this page speculatively (prefetch or prerender) rather than for a reader.
   * Covers Speculation Rules and modern browsers (Sec-Purpose), legacy Chrome (Purpose) and Firefox (X-Moz).
   *
   * @return bool
   */
  private static function is_prefetch_request(): bool {
    foreach ( array( 'HTTP_SEC_PURPOSE', 'HTTP_PURPOSE', 'HTTP_X_MOZ' ) as $header ) {
      if ( isset( $_SERVER[ $header ] ) && preg_match( '/^prefetch(?:\s*;|$)/i', trim( (string) wp_unslash( $_SERVER[ $header ] ) ) ) ) {
        return true;
      }
    }

    return false;
  }

  /**
   * Whether the post matches at least one rule group.
   *
   * @param WP_Post $post  Queried post.
   * @param array   $rules Sanitised rule groups from Memberful_Metering_Config::get().
   *
   * @return bool
   */
  private static function post_matches_any_group( WP_Post $post, array $rules ): bool {
    foreach ( $rules as $group ) {
      if ( is_array( $group ) && self::group_matches( $post, $group ) ) {
        return true;
      }
    }

    return false;
  }

  /**
   * Whether one rule group (with 'all' / 'any' match) is satisfied by the post.
   *
   * @param WP_Post $post  Queried post.
   * @param array   $group Single rule group.
   *
   * @return bool
   */
  private static function group_matches( WP_Post $post, array $group ): bool {
    $match      = isset( $group['match'] ) ? $group['match'] : 'all';
    $conditions = isset( $group['conditions'] ) && is_array( $group['conditions'] ) ? $group['conditions'] : array();

    if ( empty( $conditions ) ) {
      return false;
    }

    if ( 'any' === $match ) {
      foreach ( $conditions as $condition ) {
        if ( is_array( $condition ) && self::condition_matches( $post, $condition ) ) {
          return true;
        }
      }
      return false;
    }

    foreach ( $conditions as $condition ) {
      if ( ! is_array( $condition ) || ! self::condition_matches( $post, $condition ) ) {
        return false;
      }
    }
    return true;
  }

  /**
   * Whether one condition row is satisfied by the post.
   *
   * @param WP_Post $post      Queried post.
   * @param array   $condition Single condition row.
   *
   * @return bool
   */
  private static function condition_matches( WP_Post $post, array $condition ): bool {
    $field    = isset( $condition['field'] ) ? $condition['field'] : '';
    $operator = isset( $condition['operator'] ) ? $condition['operator'] : '';
    $values   = isset( $condition['values'] ) && is_array( $condition['values'] ) ? $condition['values'] : array();

    if ( empty( $values ) ) {
      return false;
    }

    $valid_operators = isset( Memberful_Metering_Config::FIELD_OPERATORS[ $field ] )
      ? Memberful_Metering_Config::FIELD_OPERATORS[ $field ]
      : array();
    if ( ! in_array( $operator, $valid_operators, true ) ) {
      return false;
    }

    switch ( $field ) {
      case 'post_type':
        $matches = in_array( $post->post_type, $values, true );
        break;

      case 'category':
      case 'tag':
        $taxonomy   = 'tag' === $field ? 'post_tag' : 'category';
        $post_terms = self::term_ids_for_post( $post->ID, $taxonomy );
        $matches    = ! empty( array_intersect( array_map( 'intval', $values ), $post_terms ) );
        break;

      case 'url':
        $path    = wp_parse_url( (string) get_permalink( $post->ID ), PHP_URL_PATH );
        $path    = is_string( $path ) ? $path : '';
        $matches = false;
        foreach ( $values as $fragment ) {
          if ( '' !== $fragment && false !== stripos( $path, (string) $fragment ) ) {
            $matches = true;
            break;
          }
        }
        break;

      default:
        return false;
    }

    // Negative operators (is none of / has none of / does not contain) invert the positive match.
    return in_array( $operator, Memberful_Metering_Config::NEGATIVE_OPERATORS, true ) ? ! $matches : $matches;
  }

  /**
   * Gather the IDs of all terms attached to a post in a taxonomy.
   *
   * @param int    $post_id  Post ID.
   * @param string $taxonomy Taxonomy slug ('category' or 'post_tag').
   *
   * @return array<int, int>
   */
  private static function term_ids_for_post( int $post_id, string $taxonomy ): array {
    $term_ids = wp_get_post_terms( $post_id, $taxonomy, array( 'fields' => 'ids' ) );
    if ( is_wp_error( $term_ids ) ) {
      return array();
    }

    return array_map( 'intval', $term_ids );
  }
}
