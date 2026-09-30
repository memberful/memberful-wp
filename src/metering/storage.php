<?php
/**
 * Metering storage.
 *
 * Anonymous visitors are counted in their browser (localStorage, see js/src/metering.js) so a metered page stays
 * identical for everyone and cacheable. Logged-in visitors are counted in user meta.
 *
 * @package memberful-wp
 */

/**
 * Class Memberful_Metering_Storage.
 */
class Memberful_Metering_Storage {
  /**
   * localStorage key the browser meter keeps an anonymous visitor's views under.
   */
  const STORAGE_KEY = 'memberful_metering';

  const USER_META_KEY = 'memberful_metering_views';

  /**
   * Upper bound on stored view entries per user. Far above any realistic rolling-window count; it only guards runaway
   * meta.
   */
  const MAX_VIEWS = 100;

  /**
   * Read a logged-in user's metering views from user meta.
   *
   * @param int $user_id WP user ID.
   *
   * @return array<int, int> Map of post_id => unix timestamp.
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
}
