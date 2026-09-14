<?php
/**
 * Metering storage: signed cookie for anonymous views, user meta for logged-in.
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
   * Cookies over 4 KB are dropped by the browser.
   *
   * The size is roughly MAX_VIEWS x 20 bytes of JSON, then base64 + HMAC.
   * At 100 stored entries after base64 + HMAC, the size is ~ 2 KB, at 150 it's ~ 4 KB.
   */
  const MAX_VIEWS = 100;

  /**
   * Read and verify the anonymous views cookie.
   *
   * @return array<int, int> Map of post_id => unix timestamp. Empty on missing/invalid cookie.
   */
  public static function read_anonymous_views(): array {
    if ( empty( $_COOKIE[ self::COOKIE_NAME ] ) ) {
      return array();
    }

    $cookie = wp_unslash( $_COOKIE[ self::COOKIE_NAME ] );
    if ( ! is_string( $cookie ) ) {
      return array();
    }

    $payload = self::verify( $cookie );
    if ( empty( $payload['views'] ) || ! is_array( $payload['views'] ) ) {
      return array();
    }

    return self::normalize_views( $payload['views'] );
  }

  /**
   * Sign and write the anonymous views cookie.
   *
   * @param array<int, int> $views       Map of post_id => unix timestamp.
   * @param int             $period_days Rolling-window length in days; also drives cookie expiry.
   */
  public static function write_anonymous_views( array $views, int $period_days ): void {
    $views   = self::cap( $views );
    $expires = time() + ( $period_days * DAY_IN_SECONDS );

    $payload = wp_json_encode(
      array(
        'v'     => 1,
        'views' => $views,
        'exp'   => $expires,
      )
    );

    if ( false === $payload ) {
      return;
    }

    $cookie = self::sign( $payload );
    self::send_cookie( $cookie, $expires );
    $_COOKIE[ self::COOKIE_NAME ] = $cookie;
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
   */
  public static function write_user_views( int $user_id, array $views ): void {
    update_user_meta( $user_id, self::USER_META_KEY, self::cap( $views ) );
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
   * Sign a payload string and return the full cookie value (payload.signature).
   *
   * @param string $payload JSON payload.
   *
   * @return string
   */
  private static function sign( string $payload ): string {
    $payload_b64 = self::b64url_encode( $payload );
    $signature   = hash_hmac( 'sha256', $payload_b64, wp_salt( 'auth' ), true );

    return $payload_b64 . '.' . self::b64url_encode( $signature );
  }

  /**
   * Verify a signed cookie and return the decoded payload, or [] on any failure.
   *
   * @param string $cookie Raw cookie value.
   *
   * @return array
   */
  private static function verify( string $cookie ): array {
    $parts = explode( '.', $cookie, 2 );
    if ( 2 !== count( $parts ) ) {
      return array();
    }

    list( $payload_b64, $signature_b64 ) = $parts;

    $expected = self::b64url_encode( hash_hmac( 'sha256', $payload_b64, wp_salt( 'auth' ), true ) );
    if ( ! hash_equals( $expected, $signature_b64 ) ) {
      return array();
    }

    $payload = self::b64url_decode( $payload_b64 );
    if ( false === $payload ) {
      return array();
    }

    $decoded = json_decode( $payload, true );
    if ( ! is_array( $decoded ) ) {
      return array();
    }

    // Reject a cookie past its stored expiry. prune()'s rolling window is the real enforcement.
    if ( isset( $decoded['exp'] ) && (int) $decoded['exp'] < time() ) {
      return array();
    }

    return $decoded;
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
   * Set the metering cookie with the project's standard attributes.
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

  /**
   * URL-safe base64 decode.
   *
   * @param string $value Encoded value.
   *
   * @return string|false
   */
  private static function b64url_decode( string $value ) {
    return base64_decode( strtr( $value, '-_', '+/' ) );
  }
}
