<?php
/**
 * Metering storage.
 *
 * Anonymous visitors are identified by an opaque, server-issued subject id carried in a signed, HttpOnly cookie; their
 * view counts live in a server-side transient ledger keyed by a hash of that subject. The cookie is only a pointer to
 * server state, never the state itself, so deleting or replaying it cannot rewind the meter to an attacker's advantage
 * (a fresh cookie yields a fresh, empty ledger, which the sample endpoint's cookieless-release cap and rate limiting
 * bound). Logged-in visitors
 * use user meta. The subject is minted only in uncached contexts (the admin-ajax sample endpoint, or login), never on a
 * cacheable page — so the anonymous page stays byte-identical and cacheable on hosts that strip cookies before PHP.
 *
 * @package memberful-wp
 */

/**
 * Class Memberful_Metering_Storage.
 */
class Memberful_Metering_Storage {
  const COOKIE_NAME   = 'memberful_metering';
  const USER_META_KEY = 'memberful_metering_views';

  /**
   * Transient key prefix for the server-side anonymous ledger.
   */
  const LEDGER_PREFIX = 'mbf_mtr_l_';

  /**
   * Transient key prefix recording that a subject has previously received protected content.
   */
  const PROTECTED_RELEASE_PREFIX = 'mbf_mtr_p_';

  /**
   * Object-cache group for atomic rate-limit counters (used only when a persistent object cache is available).
   */
  const CACHE_GROUP = 'memberful_metering';

  /**
   * Bytes of entropy in the opaque subject id (128-bit → 32 hex chars).
   */
  const SUBJECT_BYTES = 16;

  /**
   * Upper bound on stored view entries per subject/user. The ledger is server-side, so this only guards runaway meta;
   * it is far above any realistic rolling-window count.
   */
  const MAX_VIEWS = 100;

  /**
   * Read and validate the opaque subject id from the request cookie without minting a new one.
   *
   * Safe to call in any context (does not send headers).
   *
   * @return string|null The subject id, or null when absent/invalid.
   */
  public static function read_subject(): ?string {
    if ( empty( $_COOKIE[ self::COOKIE_NAME ] ) ) {
      return null;
    }

    $cookie = wp_unslash( $_COOKIE[ self::COOKIE_NAME ] );
    if ( ! is_string( $cookie ) ) {
      return null;
    }

    return self::verify_subject( $cookie );
  }

  /**
   * Return the subject id for this request, minting and setting the cookie when absent.
   *
   * MUST be called only from an uncached context (admin-ajax, login) — never while rendering a cacheable page, or the
   * Set-Cookie would either be dropped by the cache or baked into shared HTML.
   *
   * @param int $period_days Rolling-window length; also drives the cookie's expiry.
   *
   * @return string
   */
  public static function get_or_create_subject( int $period_days ): string {
    $subject = self::read_subject();

    if ( null === $subject ) {
      $subject = bin2hex( random_bytes( self::SUBJECT_BYTES ) );
      $expires = time() + ( max( 1, $period_days ) * DAY_IN_SECONDS );
      $signed  = self::sign( $subject, $expires );

      self::send_cookie( $signed, $expires );
      $_COOKIE[ self::COOKIE_NAME ] = $signed;
    }

    return self::apply_subject_filter( $subject );
  }

  /**
   * Read the resolved subject (the raw cookie id passed through the identity filter) without minting. Returns null when
   * no valid subject cookie is present.
   *
   * @return string|null
   */
  public static function current_subject(): ?string {
    $raw = self::read_subject();

    return ( null === $raw ) ? null : self::apply_subject_filter( $raw );
  }

  /**
   * Resolve the subject that keys a visitor's ledger: the raw cookie id, or a replacement returned by the
   * memberful_metering_subject_key filter, so minting and metering use the same value.
   *
   * @param string $raw Raw opaque cookie subject.
   *
   * @return string
   */
  private static function apply_subject_filter( string $raw ): string {
    /**
     * Filter the opaque key that identifies an anonymous visitor's meter. Passed the cookie-backed subject id; return
     * a non-empty string to replace it.
     *
     * @param string $raw The opaque, cookie-backed subject id.
     */
    $override = apply_filters( 'memberful_metering_subject_key', $raw );

    return ( is_string( $override ) && '' !== $override ) ? $override : $raw;
  }

  /**
   * Read a subject's metering views from the server-side ledger.
   *
   * @param string $subject Subject id.
   *
   * @return array<int, int> Map of post_id => unix timestamp.
   */
  public static function read_ledger_views( string $subject ): array {
    if ( '' === $subject ) {
      return array();
    }

    $raw = get_transient( self::ledger_key( $subject ) );
    if ( ! is_array( $raw ) ) {
      return array();
    }

    return self::normalize_views( $raw );
  }

  /**
   * Persist a subject's metering views to the server-side ledger.
   *
   * @param string          $subject     Subject id.
   * @param array<int, int> $views       Map of post_id => unix timestamp.
   * @param int             $period_days Rolling-window length; also the transient TTL.
   *
   * @return bool Whether the ledger was persisted.
   */
  public static function write_ledger_views( string $subject, array $views, int $period_days ): bool {
    if ( '' === $subject ) {
      return false;
    }

    return set_transient(
      self::ledger_key( $subject ),
      self::cap( $views ),
      max( 1, $period_days ) * DAY_IN_SECONDS
    );
  }

  /**
   * Extend the subject cookie after a new distinct view so it remains valid for the ledger's rolling window.
   *
   * @param int $period_days Rolling-window length in days.
   */
  public static function refresh_subject_cookie( int $period_days ): void {
    $subject = self::read_subject();
    if ( null === $subject ) {
      return;
    }

    $expires = time() + ( max( 1, $period_days ) * DAY_IN_SECONDS );
    $signed  = self::sign( $subject, $expires );

    self::send_cookie( $signed, $expires );
    $_COOKIE[ self::COOKIE_NAME ] = $signed;
  }

  /**
   * Whether this subject has successfully received protected content during the current rolling period.
   *
   * Public views deliberately do not set this marker: otherwise an attacker could record a public post before each
   * cookie rotation and bypass the cookieless protected-release cap.
   *
   * @param string $subject Subject id.
   *
   * @return bool
   */
  public static function has_protected_release( string $subject ): bool {
    return '' !== $subject && (bool) get_transient( self::protected_release_key( $subject ) );
  }

  /**
   * Mark a successful protected-content release for cookieless-cap exemption on later requests.
   *
   * @param string $subject     Subject id.
   * @param int    $period_days Rolling-window length in days.
   */
  public static function mark_protected_release( string $subject, int $period_days ): void {
    if ( '' === $subject ) {
      return;
    }

    set_transient( self::protected_release_key( $subject ), 1, max( 1, $period_days ) * DAY_IN_SECONDS );
  }

  /**
   * Read a logged-in user's metering views from user meta.
   *
   * @param int $user_id WP user ID.
   *
   * @return array<int, int>
   */
  public static function read_user_views( int $user_id ): array {
    $raw = get_user_meta( $user_id, self::USER_META_KEY, true );
    if ( ! is_array( $raw ) ) {
      return array();
    }

    return self::normalize_views( $raw );
  }

  /**
   * Persist a logged-in user's metering views to user meta.
   *
   * @param int             $user_id WP user ID.
   * @param array<int, int> $views   Map of post_id => unix timestamp.
   *
   * @return bool Whether the expected views were persisted.
   */
  public static function write_user_views( int $user_id, array $views ): bool {
    $views = self::cap( $views );
    update_user_meta( $user_id, self::USER_META_KEY, $views );

    return self::read_user_views( $user_id ) === $views;
  }

  /**
   * Atomically increment a fixed-window counter and return the new value. Atomic on a persistent object cache;
   * degrades to a best-effort transient counter (approximate under high concurrency) where none is present.
   *
   * @param string $key Counter key.
   * @param int    $ttl Window length in seconds.
   *
   * @return int
   */
  public static function incr_counter( string $key, int $ttl ): int {
    if ( wp_using_ext_object_cache() ) {
      wp_cache_add( $key, 0, self::CACHE_GROUP, $ttl );
      $new = wp_cache_incr( $key, 1, self::CACHE_GROUP );
      if ( is_int( $new ) ) {
        return $new;
      }
      // Object cache present but without a working incr: fall through to the transient counter. A given backend is
      // consistent about incr support, so reads and writes stay on one store (read_counter no longer exists to split
      // them). This never returns a constant that would disable enforcement.
    }

    // Transient counter: atomic where transients are backed by a persistent object cache; best-effort (approximate
    // under heavy concurrency) on hosts with no object cache, where WordPress exposes no atomic increment primitive.
    // Acceptable for a soft meter — the per-subject ledger cap still bounds each individual visitor regardless.
    $value = (int) get_transient( $key ) + 1;
    set_transient( $key, $value, $ttl );

    return $value;
  }

  /**
   * Drop views older than the rolling-window cutoff.
   *
   * @param array<int, int> $views       Map of post_id => unix timestamp.
   * @param int             $period_days Rolling window in days.
   *
   * @return array<int, int>
   */
  public static function prune( array $views, int $period_days ): array {
    $cutoff = time() - ( $period_days * DAY_IN_SECONDS );

    return array_filter(
      $views,
      function ( $ts ) use ( $cutoff ) {
        return (int) $ts >= $cutoff;
      }
    );
  }

  /**
   * The transient key for a subject's ledger. The subject is hashed with a site salt so the stored key never reveals
   * the cookie value.
   *
   * @param string $subject Subject id.
   *
   * @return string
   */
  private static function ledger_key( string $subject ): string {
    return self::LEDGER_PREFIX . hash( 'sha256', $subject . wp_salt( 'auth' ) );
  }

  /**
   * The transient key indicating that a subject has received protected content.
   *
   * @param string $subject Subject id.
   *
   * @return string
   */
  private static function protected_release_key( string $subject ): string {
    return self::PROTECTED_RELEASE_PREFIX . hash( 'sha256', $subject . wp_salt( 'auth' ) );
  }

  /**
   * Sign a subject id with an embedded expiry and return "subject.exp.signature".
   *
   * @param string $subject Opaque subject id.
   * @param int    $expires Unix timestamp after which the token is invalid.
   *
   * @return string
   */
  private static function sign( string $subject, int $expires ): string {
    $payload   = $subject . '.' . $expires;
    $signature = hash_hmac( 'sha256', $payload, wp_salt( 'auth' ), true );

    return $payload . '.' . self::b64url_encode( $signature );
  }

  /**
   * Verify a signed subject cookie and return the subject id, or null on any failure.
   *
   * @param string $cookie Raw cookie value.
   *
   * @return string|null
   */
  private static function verify_subject( string $cookie ): ?string {
    $parts = explode( '.', $cookie );
    if ( 3 !== count( $parts ) ) {
      return null;
    }

    list( $subject, $expires, $signature_b64 ) = $parts;

    if ( ! preg_match( '/^[a-f0-9]{' . ( self::SUBJECT_BYTES * 2 ) . '}$/', $subject ) ) {
      return null;
    }

    // Reject an expired token: past its embedded expiry it is treated as no cookie, so the caller falls back to the
    // cookieless path and its per-IP cap. This stops a retained cookie being replayed indefinitely.
    if ( ! ctype_digit( $expires ) || (int) $expires < time() ) {
      return null;
    }

    $expected = self::b64url_encode( hash_hmac( 'sha256', $subject . '.' . $expires, wp_salt( 'auth' ), true ) );
    if ( ! hash_equals( $expected, $signature_b64 ) ) {
      return null;
    }

    return $subject;
  }

  /**
   * Coerce a raw views array into the canonical shape, dropping invalid entries.
   *
   * @param array $raw Raw views (untrusted input).
   *
   * @return array<int, int>
   */
  private static function normalize_views( array $raw ): array {
    $clean = array();

    foreach ( $raw as $post_id => $timestamp ) {
      $post_id   = (int) $post_id;
      $timestamp = (int) $timestamp;

      if ( $post_id > 0 && $timestamp > 0 ) {
        $clean[ $post_id ] = $timestamp;
      }
    }

    return $clean;
  }

  /**
   * Cap the views array at MAX_VIEWS, keeping the newest entries.
   *
   * @param array<int, int> $views Map of post_id => unix timestamp.
   *
   * @return array<int, int>
   */
  private static function cap( array $views ): array {
    if ( count( $views ) <= self::MAX_VIEWS ) {
      return $views;
    }

    asort( $views );

    return array_slice( $views, -self::MAX_VIEWS, null, true );
  }

  /**
   * Set the metering (subject) cookie with the project's standard attributes.
   *
   * @param string $value   Cookie value.
   * @param int    $expires Unix timestamp for cookie expiry.
   */
  private static function send_cookie( string $value, int $expires ): void {
    setcookie(
      self::COOKIE_NAME,
      $value,
      array(
        'expires'  => $expires,
        'path'     => defined( 'COOKIEPATH' ) && COOKIEPATH ? COOKIEPATH : '/',
        'domain'   => defined( 'COOKIE_DOMAIN' ) && COOKIE_DOMAIN ? COOKIE_DOMAIN : '',
        'secure'   => is_ssl(),
        'httponly' => true,
        'samesite' => 'Lax',
      )
    );
  }

  /**
   * URL-safe base64 encode (no padding, +/ replaced with -_).
   *
   * @param string $bytes Raw bytes.
   *
   * @return string
   */
  private static function b64url_encode( string $bytes ): string {
    return rtrim( strtr( base64_encode( $bytes ), '+/', '-_' ), '=' );
  }
}
