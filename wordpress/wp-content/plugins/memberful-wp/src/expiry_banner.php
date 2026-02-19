<?php

add_action( 'wp_footer', 'memberful_wp_render_expiry_banner' );

/**
 * Renders the expiry banner on front end pages for eligible users.
 *
 * @return void
 */
function memberful_wp_render_expiry_banner() {
  if ( is_admin() || ! memberful_wp_is_connected_to_site() || ! is_user_logged_in() ) {
    return;
  }

  if ( current_user_can( 'manage_options' ) ) {
    return;
  }

  $enabled = (bool) get_option( 'memberful_expiry_banner_enabled', false );

  /**
   * Filters the expiry banner enabled status.
   *
   * @param bool $enabled The default enabled status.
   *
   * @return bool Whether the expiry banner is enabled.
   */
  $enabled = (bool) apply_filters( 'memberful_expiry_banner_enabled', $enabled );

  if ( ! $enabled ) {
    return;
  }

  $expiry_data = memberful_wp_get_soonest_expiring_subscription( get_current_user_id() );

  if ( empty( $expiry_data ) ) {
    return;
  }

  $account_url = memberful_account_url();
  $message = memberful_wp_expiry_banner_message( $expiry_data, $account_url );

  /**
   * Filters the rendered expiry banner message.
   *
   * @param string $message     The banner message HTML string.
   * @param array  $expiry_data The computed expiry data for the current user.
   *
   * @return string The filtered banner message HTML.
   */
  $message = apply_filters( 'memberful_expiry_banner_message', $message, $expiry_data );

  $is_expired = ! empty( $expiry_data['is_expired'] );
  $aria_role = $is_expired ? 'alert' : 'status';
  $aria_live = $is_expired ? 'assertive' : 'polite';

  /**
   * Filters the ARIA role used for the expiry banner live region.
   *
   * @param string $aria_role   The computed ARIA role.
   * @param array  $expiry_data The computed expiry data for the current user.
   *
   * @return string The ARIA role for the banner.
   */
  $aria_role = (string) apply_filters( 'memberful_expiry_banner_aria_role', $aria_role, $expiry_data );

  /**
   * Filters the ARIA live mode used for the expiry banner.
   *
   * @param string $aria_live   The computed ARIA live value.
   * @param array  $expiry_data The computed expiry data for the current user.
   *
   * @return string The ARIA live value for the banner.
   */
  $aria_live = (string) apply_filters( 'memberful_expiry_banner_aria_live', $aria_live, $expiry_data );

  ob_start();
  memberful_wp_render(
    'expiry-banner',
    array(
      'message' => $message,
      'aria_role' => $aria_role,
      'aria_live' => $aria_live,
    )
  );
  $html = ob_get_clean();

  /**
   * Filters the full expiry banner HTML output.
   *
   * @param string $html        The full banner HTML output.
   * @param array  $expiry_data The computed expiry data for the current user.
   *
   * @return string The filtered banner HTML output.
   */
  echo apply_filters( 'memberful_expiry_banner_html', $html, $expiry_data );
}

/**
 * Returns soonest subscription expiry data for a user within threshold.
 *
 * @param int $user_id User ID.
 *
 * @return array|null
 */
function memberful_wp_get_soonest_expiring_subscription( $user_id ) {
  $subscriptions = get_user_meta( $user_id, 'memberful_subscription', true );

  if ( empty( $subscriptions ) || ! is_array( $subscriptions ) ) {
    return null;
  }

  $days_threshold = min( 90, max( 1, (int) get_option( 'memberful_expiry_banner_days', 7 ) ) );

  /**
   * Filters the number of days before expiry that triggers the banner.
   *
   * @param int $days_threshold The configured day threshold.
   *
   * @return int The filtered day threshold.
   */
  $days_threshold = (int) apply_filters( 'memberful_expiry_banner_days_threshold', $days_threshold );
  $days_threshold = min( 90, max( 1, $days_threshold ) );

  $now = time();
  $threshold_timestamp = $now + ( $days_threshold * DAY_IN_SECONDS );
  $soonest = null;

  foreach ( $subscriptions as $subscription ) {
    if ( empty( $subscription['expires_at'] ) ) {
      continue;
    }

    $expires_at = memberful_wp_parse_expiry_timestamp( $subscription['expires_at'] );

    if ( empty( $expires_at ) ) {
      continue;
    }

    if ( $expires_at > $threshold_timestamp ) {
      continue;
    }

    if ( null === $soonest || $expires_at < $soonest['expires_at'] ) {
      $seconds_remaining = $expires_at - $now;
      $is_expired = $seconds_remaining < 0;
      $days_remaining = $is_expired ? 0 : (int) ceil( $seconds_remaining / DAY_IN_SECONDS );

      $soonest = array(
        'expires_at' => $expires_at,
        'days_remaining' => $days_remaining,
        'is_expired' => $is_expired,
      );
    }
  }

  return $soonest;
}

/**
 * Builds the user-facing banner message.
 *
 * @param array  $expiry_data Expiry information array.
 * @param string $account_url Memberful account URL.
 *
 * @return string
 */
function memberful_wp_expiry_banner_message( array $expiry_data, $account_url ) {
  $link = wp_sprintf(
    '<a href="%s">%s</a>',
    esc_url( $account_url ),
    esc_html__( 'Update your membership', 'memberful' )
  );

  if ( ! empty( $expiry_data['is_expired'] ) ) {
    return wp_sprintf(
      /* translators: %s is the update membership link. */
      __( 'Your membership has expired. %s.', 'memberful' ),
      $link
    );
  }

  if ( (int) $expiry_data['days_remaining'] <= 0 ) {
    return wp_sprintf(
      /* translators: %s is the update membership link. */
      __( 'Your membership expires today. %s.', 'memberful' ),
      $link
    );
  }

  return wp_sprintf(
    /* translators: 1: Number of days remaining. 2: Update membership link. */
    _n(
      'Your membership expires in %1$d day. %2$s.',
      'Your membership expires in %1$d days. %2$s.',
      (int) $expiry_data['days_remaining'],
      'memberful'
    ),
    (int) $expiry_data['days_remaining'],
    $link
  );
}

/**
 * Converts an expiry value into a Unix timestamp.
 *
 * @param mixed $expires_at Expiry value from user meta.
 *
 * @return int
 */
function memberful_wp_parse_expiry_timestamp( $expires_at ) {
  if ( is_numeric( $expires_at ) ) {
    return (int) $expires_at;
  }

  $parsed_time = strtotime( (string) $expires_at );

  if ( false === $parsed_time ) {
    return 0;
  }

  return (int) $parsed_time;
}
