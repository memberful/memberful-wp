<?php

add_action( 'wp_body_open', 'memberful_wp_render_expiry_banner' );
add_action( 'wp_footer', 'memberful_wp_render_expiry_banner_fallback' );

/**
 * Renders the expiry banner in footer for themes without wp_body_open support.
 *
 * @return void
 */
function memberful_wp_render_expiry_banner_fallback() {
  if ( did_action( 'wp_body_open' ) ) {
    return;
  }

  memberful_wp_render_expiry_banner();
}

/**
 * Renders the expiry banner on front end pages for eligible users.
 *
 * @return void
 */
function memberful_wp_render_expiry_banner() {
  static $has_rendered = false;

  if ( $has_rendered ) {
    return;
  }

  if ( is_admin() || ! memberful_wp_is_connected_to_site() || ! is_user_logged_in() ) {
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
  $aria_attributes = array(
    'role' => $is_expired ? 'alert' : 'status',
    'live' => $is_expired ? 'assertive' : 'polite',
  );

  /**
   * Filters the ARIA attributes used for the expiry banner live region.
   *
   * @param array $aria_attributes {
   *   ARIA attributes for the expiry banner.
   *
   *   @type string $role The ARIA role value.
   *   @type string $live The ARIA live mode value.
   * }
   * @param array $expiry_data The computed expiry data for the current user.
   *
   * @return array The filtered ARIA attribute values.
   */
  $aria_attributes = apply_filters( 'memberful_expiry_banner_aria_attributes', $aria_attributes, $expiry_data );
  $aria_attributes = is_array( $aria_attributes ) ? $aria_attributes : array();
  $aria_role = isset( $aria_attributes['role'] ) ? (string) $aria_attributes['role'] : 'status';
  $aria_live = isset( $aria_attributes['live'] ) ? (string) $aria_attributes['live'] : 'polite';

  $has_rendered = true;
  memberful_wp_enqueue_expiry_banner_script();

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
 * Enqueues the expiry banner script.
 *
 * @return void
 */
function memberful_wp_enqueue_expiry_banner_script() {
  static $is_enqueued = false;

  if ( $is_enqueued ) {
    return;
  }

  $script_asset_path = MEMBERFUL_DIR . '/js/build/expiry-banner.asset.php';
  $script_asset_info = file_exists( $script_asset_path )
    ? include $script_asset_path
    : array(
      'dependencies' => array(),
      'version' => MEMBERFUL_VERSION,
    );

  wp_enqueue_script(
    'memberful-expiry-banner',
    plugins_url( 'js/build/expiry-banner.js', MEMBERFUL_PLUGIN_FILE ),
    $script_asset_info['dependencies'],
    $script_asset_info['version'],
    true
  );

  $is_enqueued = true;
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
  $expiring_subscriptions_count = 0;
  $active_subscriptions_count = 0;

  foreach ( $subscriptions as $subscription ) {
    if ( empty( $subscription['expires_at'] ) ) {
      ++$active_subscriptions_count;
      continue;
    }

    $expires_at = memberful_wp_parse_expiry_timestamp( $subscription['expires_at'] );

    if ( empty( $expires_at ) ) {
      ++$active_subscriptions_count;
      continue;
    }

    if ( $expires_at > $threshold_timestamp ) {
      ++$active_subscriptions_count;
      continue;
    }

    $seconds_remaining = $expires_at - $now;
    $is_expired = $seconds_remaining < 0;

    if ( ! $is_expired && memberful_wp_subscription_has_autorenew_enabled( $subscription ) ) {
      ++$active_subscriptions_count;
      continue;
    }

    ++$expiring_subscriptions_count;

    $should_replace_soonest = false;

    if ( null === $soonest ) {
      $should_replace_soonest = true;
    } else {
      $soonest_is_expired = ! empty( $soonest['is_expired'] );

      if ( $soonest_is_expired && ! $is_expired ) {
        $should_replace_soonest = true;
      } elseif ( $soonest_is_expired === $is_expired && $expires_at < $soonest['expires_at'] ) {
        $should_replace_soonest = true;
      }
    }

    if ( $should_replace_soonest ) {
      $days_remaining = memberful_wp_get_subscription_days_remaining( $expires_at, $now );

      $soonest = array(
        'expires_at' => $expires_at,
        'days_remaining' => $days_remaining,
        'is_expired' => $is_expired,
      );
    }
  }

  if ( null === $soonest ) {
    return null;
  }

  $soonest['expiring_subscriptions_count'] = $expiring_subscriptions_count;
  $soonest['active_subscriptions_count'] = $active_subscriptions_count;

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
    esc_html__( 'Renew now', 'memberful' )
  );
  $expiring_subscriptions_count = max( 1, (int) ( $expiry_data['expiring_subscriptions_count'] ?? 1 ) );
  $active_subscriptions_count = max( 0, (int) ( $expiry_data['active_subscriptions_count'] ?? 0 ) );
  $is_mixed_subscriptions = $active_subscriptions_count > 0;
  $has_multiple_expiring_subscriptions = $expiring_subscriptions_count > 1;

  if ( ! empty( $expiry_data['is_expired'] ) ) {
    if ( $has_multiple_expiring_subscriptions ) {
      if ( $is_mixed_subscriptions ) {
        return wp_sprintf(
          /* translators: %s is the renewal link. */
          __( 'Some of your subscriptions have expired. %s.', 'memberful' ),
          $link
        );
      }

      return wp_sprintf(
        /* translators: %s is the renewal link. */
        __( 'Your subscriptions have expired. %s.', 'memberful' ),
        $link
      );
    }

    if ( $is_mixed_subscriptions ) {
      return wp_sprintf(
        /* translators: %s is the renewal link. */
        __( 'One of your subscriptions has expired. %s.', 'memberful' ),
        $link
      );
    }

    return wp_sprintf(
      /* translators: %s is the renewal link. */
      __( 'Your subscription has expired. %s.', 'memberful' ),
      $link
    );
  }

  if ( (int) $expiry_data['days_remaining'] <= 0 ) {
    if ( $has_multiple_expiring_subscriptions ) {
      if ( $is_mixed_subscriptions ) {
        return wp_sprintf(
          /* translators: %s is the renewal link. */
          __( 'Some of your subscriptions expire today. %s.', 'memberful' ),
          $link
        );
      }

      return wp_sprintf(
        /* translators: %s is the renewal link. */
        __( 'Your subscriptions expire today. %s.', 'memberful' ),
        $link
      );
    }

    if ( $is_mixed_subscriptions ) {
      return wp_sprintf(
        /* translators: %s is the renewal link. */
        __( 'One of your subscriptions expires today. %s.', 'memberful' ),
        $link
      );
    }

    return wp_sprintf(
      /* translators: %s is the renewal link. */
      __( 'Your subscription expires today. %s.', 'memberful' ),
      $link
    );
  }

  if ( $has_multiple_expiring_subscriptions ) {
    if ( $is_mixed_subscriptions ) {
      return wp_sprintf(
        /* translators: 1: Number of days remaining. 2: Renewal link. */
        _n(
          'Some of your subscriptions expire in %1$d day. %2$s.',
          'Some of your subscriptions expire in %1$d days. %2$s.',
          (int) $expiry_data['days_remaining'],
          'memberful'
        ),
        (int) $expiry_data['days_remaining'],
        $link
      );
    }

    return wp_sprintf(
      /* translators: 1: Number of days remaining. 2: Renewal link. */
      _n(
        'Your subscriptions expire in %1$d day. %2$s.',
        'Your subscriptions expire in %1$d days. %2$s.',
        (int) $expiry_data['days_remaining'],
        'memberful'
      ),
      (int) $expiry_data['days_remaining'],
      $link
    );
  }

  if ( $is_mixed_subscriptions ) {
    return wp_sprintf(
      /* translators: 1: Number of days remaining. 2: Renewal link. */
      _n(
        'One of your subscriptions expires in %1$d day. %2$s.',
        'One of your subscriptions expires in %1$d days. %2$s.',
        (int) $expiry_data['days_remaining'],
        'memberful'
      ),
      (int) $expiry_data['days_remaining'],
      $link
    );
  }

  return wp_sprintf(
    /* translators: 1: Number of days remaining. 2: Renewal link. */
    _n(
      'Your subscription expires in %1$d day. %2$s.',
      'Your subscription expires in %1$d days. %2$s.',
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

/**
 * Calculates remaining subscription days in the site timezone.
 *
 * @param int $expires_at Subscription expiry timestamp.
 * @param int $now        Current timestamp.
 *
 * @return int
 */
function memberful_wp_get_subscription_days_remaining( $expires_at, $now ) {
  if ( $expires_at <= $now ) {
    return 0;
  }

  $timezone = wp_timezone();
  $current_date = wp_date( 'Y-m-d', $now, $timezone );
  $expiry_date = wp_date( 'Y-m-d', $expires_at, $timezone );

  if ( $current_date === $expiry_date ) {
    return 0;
  }

  $current_date_object = date_create_immutable_from_format( '!Y-m-d', $current_date, $timezone );
  $expiry_date_object = date_create_immutable_from_format( '!Y-m-d', $expiry_date, $timezone );

  if ( false === $current_date_object || false === $expiry_date_object ) {
    return (int) ceil( ( $expires_at - $now ) / DAY_IN_SECONDS );
  }

  $days_remaining = (int) $current_date_object->diff( $expiry_date_object )->format( '%a' );

  return max( 1, $days_remaining );
}

/**
 * Checks whether subscription auto-renew is enabled.
 *
 * @param array $subscription Subscription data from user meta.
 *
 * @return bool
 */
function memberful_wp_subscription_has_autorenew_enabled( array $subscription ) {
  if ( ! array_key_exists( 'autorenew', $subscription ) ) {
    return false;
  }

  return wp_validate_boolean( $subscription['autorenew'] );
}
