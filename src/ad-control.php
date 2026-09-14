<?php
/**
 * Ad provider settings helpers.
 *
 * @package memberful-wp
 */

/**
 * Return the default settings for ad providers.
 *
 * @return array The default ad provider settings.
 */
function memberful_wp_ad_provider_settings_defaults() {
  return array(
    'enabled' => false,
    'disabled_plans' => array(),
    'disable_for_all_subscribers' => false,
    'disable_for_logged_in' => false,
  );
}

/**
 * Get the stored ad provider settings, merged with defaults.
 *
 * @return array The ad provider settings keyed by provider identifier.
 */
function memberful_wp_ad_provider_get_settings() {
  $stored_settings = get_option( 'memberful_ad_provider_settings', array() );
  $stored_settings = is_array( $stored_settings ) ? $stored_settings : array();

  $providers = Memberful_Wp_Integration_Ad_Provider_Manager::instance()->get_all_providers();
  $settings = array();

  foreach ( $providers as $provider_id => $provider ) {
    $defaults = memberful_wp_ad_provider_settings_defaults();
    $provider_defaults = $provider->get_ad_provider_settings();

    if ( is_array( $provider_defaults ) ) {
      $defaults = wp_parse_args( $provider_defaults, $defaults );
    }

    $provider_settings = isset( $stored_settings[ $provider_id ] ) && is_array( $stored_settings[ $provider_id ] )
      ? $stored_settings[ $provider_id ]
      : array();

    $settings[ $provider_id ] = wp_parse_args( $provider_settings, $defaults );

    if ( ! isset( $settings[ $provider_id ]['disabled_plans'] ) || ! is_array( $settings[ $provider_id ]['disabled_plans'] ) ) {
      $settings[ $provider_id ]['disabled_plans'] = array();
    }

    $settings[ $provider_id ]['disabled_plans'] = array_map( 'intval', $settings[ $provider_id ]['disabled_plans'] );
  }

  return $settings;
}

/**
 * Sanitise ad provider settings from the admin form.
 *
 * @param array $raw_settings The raw settings from the request.
 * @param array $provider_ids The allowed provider identifiers.
 * @param array $plan_ids The allowed subscription plan IDs.
 * @return array The sanitised settings.
 */
function memberful_wp_ad_provider_sanitise_settings( $raw_settings, $provider_ids, $plan_ids ) {
  $sanitised = array();
  $allowed_plan_ids = array_map( 'intval', $plan_ids );

  foreach ( $provider_ids as $provider_id ) {
    $provider_settings = isset( $raw_settings[ $provider_id ] ) && is_array( $raw_settings[ $provider_id ] )
      ? $raw_settings[ $provider_id ]
      : array();

    $disabled_plans = empty( $provider_settings['disabled_plans'] )
      ? array()
      : array_map( 'intval', (array) $provider_settings['disabled_plans'] );

    $disabled_plans = array_values( array_unique( array_intersect( $disabled_plans, $allowed_plan_ids ) ) );

    $sanitised[ $provider_id ] = array(
      'enabled' => isset( $provider_settings['enabled'] ),
      'disabled_plans' => $disabled_plans,
      'disable_for_all_subscribers' => isset( $provider_settings['disable_for_all_subscribers'] ),
      'disable_for_logged_in' => isset( $provider_settings['disable_for_logged_in'] ),
    );

    /**
     * Filter the sanitised ad provider settings.
     *
     * @param array $sanitised The sanitised ad provider settings.
     * @param string $provider_id The identifier of the ad provider.
     * @param array $provider_settings The raw provider settings.
     * @param array $allowed_plan_ids The allowed plan IDs.
     * @return array The filtered sanitised ad provider settings.
     */
    $filtered_settings = apply_filters(
      'memberful_ad_provider_sanitised_settings',
      $sanitised[ $provider_id ],
      $provider_id,
      $provider_settings,
      $allowed_plan_ids
    );
    if ( is_array( $filtered_settings ) ) {
      $sanitised[ $provider_id ] = $filtered_settings;
    }
  }

  return $sanitised;
}
