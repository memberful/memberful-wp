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

  /**
   * Per-request decision cache keyed by post ID.
   *
   * @var array<int, array{decision: string, remaining: int}>
   */
  private static $decisions = array();

  /**
   * Register hooks. Called once from src/metering.php.
   */
  public static function register(): void {
    add_action( 'template_redirect', array( __CLASS__, 'on_template_redirect' ) );
    add_filter( 'memberful_paywall_free_view_limit', array( __CLASS__, 'filter_paywall_free_view_limit' ) );
  }

  /**
   * Compute and cache the metering decision for the singular post under view.
   */
  public static function on_template_redirect(): void {
    if ( ! self::is_metered_request() ) {
      return;
    }

    $config = Memberful_Metering_Config::get();
    if ( empty( $config['enabled'] ) || empty( $config['rules'] ) ) {
      return;
    }

    // Without the global paywall there is nothing to show once the meter trips - the post would render blank - so
    // treat an unconfigured paywall as "not metered". The Metering screen warns about this state.
    if ( ! get_option( 'memberful_use_global_marketing' ) ) {
      return;
    }

    $post = get_queried_object();
    if ( ! ( $post instanceof WP_Post ) ) {
      return;
    }

    if ( get_post_meta( $post->ID, 'memberful_metering_exempt', true ) ) {
      self::cache( $post->ID, self::DECISION_IGNORE );
      return;
    }

    $exclude_rules = $config['exclude_rules'];

    if (
      ! self::post_matches_any_group( $post, $config['rules'] )
      || self::post_matches_any_group( $post, $exclude_rules )
    ) {
      self::cache( $post->ID, self::DECISION_IGNORE );
      return;
    }

    $user_id              = get_current_user_id();
    $has_plan_or_download = $user_id && (
      ! empty( memberful_wp_user_plans_subscribed_to( $user_id ) )
      || ! empty( memberful_wp_user_downloads( $user_id ) )
    );
    if ( $has_plan_or_download ) {
      self::cache( $post->ID, self::DECISION_IGNORE );
      return;
    }

    $post_is_protected = ! memberful_can_user_access_post( 0, $post->ID );

    if ( $post_is_protected ) {
      // Visitors already entitled to the post read it in full and are never metered.
      if ( $user_id && memberful_can_user_access_post( $user_id, $post->ID ) ) {
        self::cache( $post->ID, self::DECISION_IGNORE );
        return;
      }

      // Otherwise the post only enters the meter when the publisher opted in.
      if ( empty( $config['apply_to_protected_posts'] ) ) {
        self::cache( $post->ID, self::DECISION_IGNORE );
        return;
      }
    }

    $period_days = (int) $config['period_days'];
    $limit       = max( 0, $user_id ? (int) $config['registered_limit'] : (int) $config['anonymous_limit'] );

    if ( 0 === $limit ) {
      self::emit_no_cache_headers();
      self::cache( $post->ID, self::DECISION_TRIP_METER, 0 );
      return;
    }

    $views       = $user_id
      ? Memberful_Metering_Storage::read_user_views( $user_id )
      : Memberful_Metering_Storage::read_anonymous_views();
    $views       = Memberful_Metering_Storage::prune( $views, $period_days );

    $already_counted = isset( $views[ $post->ID ] );

    if ( ! $already_counted && count( $views ) >= $limit ) {
      self::emit_no_cache_headers();
      self::cache( $post->ID, self::DECISION_TRIP_METER, 0 );
      return;
    }

    if ( ! $already_counted ) {
      // Browsers drop a failed prefetch and load the page normally on click, which is then counted. Serving the
      // article uncounted instead would let a forged prefetch header read every post for free.
      if ( self::is_prefetch_request() ) {
        self::emit_no_cache_headers();
        status_header( 503 );
        exit;
      }

      $views[ $post->ID ] = time();

      if ( $user_id ) {
        Memberful_Metering_Storage::write_user_views( $user_id, $views );
      } else {
        Memberful_Metering_Storage::write_anonymous_views( $views, $period_days );
      }
    }

    self::emit_no_cache_headers();
    self::cache( $post->ID, self::DECISION_ALLOW_SAMPLE, max( 0, $limit - count( $views ) ) );
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

    if ( ! $post_id || self::DECISION_TRIP_METER !== self::get_current_decision( $post_id ) ) {
      return $limit;
    }

    $config = Memberful_Metering_Config::get();

    return (int) ( get_current_user_id() ? $config['registered_limit'] : $config['anonymous_limit'] );
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
   * Whether the browser fetched this page speculatively (prefetch, prerender, or a Safari preview) rather than for a
   * reader. Covers Speculation Rules and modern browsers (Sec-Purpose), legacy Chrome (Purpose), Firefox (X-Moz)
   * and WebKit (X-Purpose).
   *
   * @return bool
   */
  private static function is_prefetch_request(): bool {
    foreach ( array( 'HTTP_SEC_PURPOSE', 'HTTP_PURPOSE', 'HTTP_X_MOZ', 'HTTP_X_PURPOSE' ) as $header ) {
      if ( isset( $_SERVER[ $header ] ) && preg_match( '/prefetch|prerender|preview/i', (string) wp_unslash( $_SERVER[ $header ] ) ) ) {
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
        $post_terms = self::term_slugs_for_post( $post->ID, $taxonomy );
        $matches    = ! empty( array_intersect( $values, $post_terms ) );
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
   * Gather the lowercased slugs and names of all terms attached to a post in a taxonomy.
   *
   * @param int    $post_id  Post ID.
   * @param string $taxonomy Taxonomy slug ('category' or 'post_tag').
   *
   * @return array<int, string>
   */
  private static function term_slugs_for_post( int $post_id, string $taxonomy ): array {
    $terms = wp_get_post_terms( $post_id, $taxonomy, array( 'fields' => 'all' ) );
    if ( is_wp_error( $terms ) || empty( $terms ) ) {
      return array();
    }

    $result = array();
    foreach ( $terms as $term ) {
      $result[] = strtolower( $term->slug );
      $result[] = strtolower( $term->name );
    }

    return $result;
  }
}
