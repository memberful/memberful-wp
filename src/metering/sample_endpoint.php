<?php
/**
 * Uncacheable admin-ajax endpoint that mirrors public views and releases protected post bodies while an anonymous
 * visitor is within their free allowance. admin-ajax is never edge-cached and still receives cookies, so the
 * server-issued subject cookie and shared ledger work even on hosts that strip cookies from cacheable page views.
 *
 * Release is decided server-side against the subject's ledger (never the client's self-reported count), the request is
 * limited to same-origin callers, and both per-IP and site-wide rate limits bound scripted enumeration/drain attempts.
 *
 * @package memberful-wp
 */

/**
 * Class Memberful_Metering_Sample.
 */
class Memberful_Metering_Sample {
  const ACTION = 'memberful_metering_sample';

  /**
   * Allowed values of the `op` request field.
   */
  const OPS = array( 'record_public', 'sample' );

  /**
   * Max calls per operation and client IP per minute before returning 429.
   */
  const RATE_PER_IP = 30;

  /**
   * Max calls per operation site-wide per minute before returning 429 (botnet circuit-breaker).
   */
  const RATE_GLOBAL = 600;

  /**
   * Max first protected-body releases per client IP per hour for subjects without a previous protected release.
   *
   * A genuine reader spends this quota only on their first protected sample. A scraper that drops or rotates subjects
   * spends it on every fresh allowance. Public views never grant an exemption, so recording a public post cannot be
   * used to bypass the protected-content cap. Filterable; 0 disables the cap.
   */
  const COOKIELESS_RELEASE_CAP = 20;

  /**
   * Register the logged-out handler only. Logged-in visitors bypass the cache and are decided on the page path.
   */
  public static function register(): void {
    add_action( 'wp_ajax_nopriv_' . self::ACTION, array( __CLASS__, 'handle' ) );
  }

  /**
   * Mirror public views or decide server-side whether to release a protected post body.
   *
   * No nonce: a page-embedded nonce goes stale in cache. CSRF is mitigated by the same-origin check. Every supplied post
   * ID is revalidated, and protected releases remain bounded by the subject ledger plus per-IP/global limits.
   */
  public static function handle(): void {
    nocache_headers();

    if ( self::is_cross_origin() ) {
      wp_send_json_error( array( 'code' => 'forbidden' ), 403 );
    }

    $op = (string) filter_input( INPUT_POST, 'op' );
    if ( ! in_array( $op, self::OPS, true ) ) {
      wp_send_json_error( array( 'code' => 'bad_request' ), 400 );
    }

    if ( self::is_rate_limited( $op ) ) {
      wp_send_json_error( array( 'code' => 'rate_limited' ), 429 );
    }

    if ( 'record_public' === $op ) {
      $post_ids = self::request_post_ids( 'post_ids' );
      if ( empty( $post_ids ) ) {
        wp_send_json_error( array( 'code' => 'bad_request' ), 400 );
      }

      wp_send_json_success(
        array(
          'synced' => Memberful_Metering_Access::record_public_views( $post_ids ),
        )
      );
    }

    $post_id = (int) filter_input( INPUT_POST, 'post_id', FILTER_SANITIZE_NUMBER_INT );
    if ( $post_id <= 0 ) {
      wp_send_json_error( array( 'code' => 'bad_request' ), 400 );
    }

    if ( ! Memberful_Metering_Access::is_releasable_sample( $post_id ) ) {
      wp_send_json_success(
        array(
          'released'  => false,
          'remaining' => 0,
          'synced'    => array(),
        )
      );
    }

    // Re-submit local views before evaluating the protected post so a replaced subject can rebuild its public history.
    // Public records may mint a subject but never grant the protected-release cap exemption.
    $public_post_ids       = self::request_post_ids( 'public_post_ids' );
    $synced                = Memberful_Metering_Access::record_public_views( $public_post_ids );
    $subject               = Memberful_Metering_Storage::current_subject();
    $has_protected_release = Memberful_Metering_Access::subject_has_protected_release( $subject );
    $sync_failed           = count( $synced ) !== count( $public_post_ids );

    if ( $sync_failed || ! Memberful_Metering_Access::sample_within_allowance( $post_id, $subject ) ) {
      wp_send_json_success(
        array(
          'released'  => false,
          'remaining' => 0,
          'synced'    => $synced,
        )
      );
    }

    if ( ! $has_protected_release && self::cookieless_over_cap() ) {
      wp_send_json_success(
        array(
          'released'  => false,
          'remaining' => 0,
          'synced'    => $synced,
        )
      );
    }

    $result = Memberful_Metering_Access::evaluate_sample( $post_id );

    if ( empty( $result['released'] ) ) {
      wp_send_json_success(
        array(
          'released'  => false,
          'remaining' => 0,
          'synced'    => $synced,
        )
      );
    }

    $subject = Memberful_Metering_Storage::current_subject();
    if ( null !== $subject ) {
      Memberful_Metering_Storage::mark_protected_release(
        $subject,
        (int) Memberful_Metering_Config::get()['period_days']
      );
    }

    wp_send_json_success(
      array(
        'released'  => true,
        'remaining' => (int) $result['remaining'],
        'synced'    => $synced,
        'html'      => memberful_wp_render_metered_body( get_post( $post_id ) ),
      )
    );
  }

  /**
   * AJAX args passed to the front-end runtime via wp_localize_script.
   *
   * @return array
   */
  public static function script_args(): array {
    return array(
      'ajaxUrl' => admin_url( 'admin-ajax.php' ),
      'action'  => self::ACTION,
    );
  }

  /**
   * Read a bounded, de-duplicated list of positive post IDs from an array request field.
   *
   * @param string $field Request field name.
   *
   * @return array<int>
   */
  private static function request_post_ids( string $field ): array {
    if ( empty( $_POST[ $field ] ) || ! is_array( $_POST[ $field ] ) ) {
      return array();
    }

    $post_ids = array_values(
      array_unique(
        array_filter(
          array_map( 'intval', wp_unslash( $_POST[ $field ] ) ),
          function ( $post_id ) {
            return $post_id > 0;
          }
        )
      )
    );

    return array_slice( $post_ids, 0, Memberful_Metering_Storage::MAX_VIEWS );
  }

  /**
   * Whether the request carries an Origin/Referer that is present and does NOT match this site. A missing header is
   * not treated as cross-origin (some browsers/privacy modes omit it); the rate limits and ledger cover that case.
   *
   * @return bool
   */
  private static function is_cross_origin(): bool {
    $source = '';
    if ( ! empty( $_SERVER['HTTP_ORIGIN'] ) ) {
      $source = (string) wp_unslash( $_SERVER['HTTP_ORIGIN'] );
    } elseif ( ! empty( $_SERVER['HTTP_REFERER'] ) ) {
      $source = (string) wp_unslash( $_SERVER['HTTP_REFERER'] );
    }

    if ( '' === $source ) {
      return false;
    }

    $source_host = wp_parse_url( $source, PHP_URL_HOST );
    $site_host   = wp_parse_url( home_url(), PHP_URL_HOST );

    if ( ! is_string( $source_host ) || ! is_string( $site_host ) ) {
      return true;
    }

    return strtolower( $source_host ) !== strtolower( $site_host );
  }

  /**
   * Coarse per-IP and site-wide rate limiting via per-operation transient counters. Public synchronization therefore
   * cannot consume the protected sample budget, while both operations remain bounded independently.
   *
   * @param string $op Valid endpoint operation.
   *
   * @return bool True when the caller should be rejected with 429.
   */
  private static function is_rate_limited( string $op ): bool {
    $bucket     = ( 'record_public' === $op ) ? 'public' : 'sample';
    $ip_key     = 'mbf_mtr_rl_' . $bucket . '_' . hash( 'sha256', self::client_ip() . wp_salt( 'auth' ) );
    $global_key = 'mbf_mtr_rl_global_' . $bucket;

    // Increment first, then compare the returned value: concurrent requests get distinct atomic counts, so they cannot
    // all observe an under-limit value and slip through. Over-limit requests still increment, which keeps them blocked.
    $ip_hits     = Memberful_Metering_Storage::incr_counter( $ip_key, MINUTE_IN_SECONDS );
    $global_hits = Memberful_Metering_Storage::incr_counter( $global_key, MINUTE_IN_SECONDS );

    return ( $ip_hits > self::RATE_PER_IP || $global_hits > self::RATE_GLOBAL );
  }

  /**
   * The caller's IP from REMOTE_ADDR (the only source not attacker-spoofable without proxy access), validated.
   *
   * @return string
   */
  private static function client_ip(): string {
    $ip = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) wp_unslash( $_SERVER['REMOTE_ADDR'] ) : '';

    return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '0.0.0.0';
  }

  /**
   * Atomically count this cookieless request against the client IP's hourly quota and report whether the quota is now
   * exceeded. Incrementing as part of the check (rather than checking, then incrementing only after release) means
   * concurrent cookieless requests get distinct counts and cannot all slip under the cap.
   *
   * @return bool
   */
  private static function cookieless_over_cap(): bool {
    /**
     * Filter the per-IP hourly cap on cookieless protected-body releases. Return 0 to disable the cap.
     *
     * @param int $cap Default COOKIELESS_RELEASE_CAP.
     */
    $cap = (int) apply_filters( 'memberful_metering_cookieless_release_cap', self::COOKIELESS_RELEASE_CAP );

    if ( $cap <= 0 ) {
      return false;
    }

    return Memberful_Metering_Storage::incr_counter( self::cookieless_key(), HOUR_IN_SECONDS ) > $cap;
  }

  /**
   * Transient key for the per-IP cookieless-release counter (IP hashed with a site salt).
   *
   * @return string
   */
  private static function cookieless_key(): string {
    return 'mbf_mtr_cl_' . hash( 'sha256', self::client_ip() . wp_salt( 'auth' ) );
  }
}
