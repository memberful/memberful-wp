<?php
/**
 * Paywall builder HTML renderer
 *
 * Produces the markup that backs the global paywall content and the live
 * admin preview. Safe to call with any array shape — unknown keys fall back
 * to Memberful_Paywall_Config::defaults().
 *
 * @package memberful-wp
 */

/**
 * Renders a paywall config array to HTML.
 */
class Memberful_Paywall_Renderer {
  /**
   * Flag to determine if we should load paywall styles.
   *
   * @var bool
   */
  private static $should_print_styles = false;

  /**
   * Register the actions on plugin load.
   */
  public static function register(): void {
    add_filter( 'memberful_wp_protect_content', array( __CLASS__, 'protect_content' ) );
    add_action( 'wp_footer', array( __CLASS__, 'maybe_print_styles' ) );
    add_filter( 'memberful_teaser_css', array( __CLASS__, 'filter_teaser_css' ) );
  }

  /**
   * Suppress the legacy inline teaser fade when the builder paywall owns the fade via paywall.css.
   *
   * @param string $css Legacy inline teaser CSS block.
   *
   * @return string
   */
  public static function filter_teaser_css( string $css ): string {
    return self::is_builder_mode() ? '' : $css;
  }

  /**
   * Conditionally flag paywall loading.
   *
   * @param string $content Content.
   *
   * @return string
   */
  public static function protect_content( string $content ): string {
    if ( self::is_builder_mode() ) {
      self::$should_print_styles = true;
    }

    /**
     * Filter the protected paywall content before it is returned.
     *
     * @param string $content Rendered paywall content.
     */
    return apply_filters( 'memberful_paywall_protected_content', $content );
  }

  /**
   * Render paywall styles.
   */
  public static function maybe_print_styles(): void {
    if ( ! file_exists( MEMBERFUL_DIR . '/stylesheets/paywall.css' ) ) {
      return;
    }

    /**
     * Filter whether the bundled paywall stylesheet should be enqueued.
     *
     * @param bool $should_print_styles Whether the stylesheet will be enqueued.
     */
    if ( ! apply_filters( 'memberful_paywall_print_styles', self::$should_print_styles ) ) {
      return;
    }

    wp_enqueue_style(
      'memberful-paywall',
      MEMBERFUL_URL . '/stylesheets/paywall.css',
      array(),
      filemtime( MEMBERFUL_DIR . '/stylesheets/paywall.css' )
    );
  }

  /**
   * Render a paywall config to HTML.
   *
   * @param array $config      Config shape from Memberful_Paywall_Config::get().
   * @param bool  $interactive Whether links should navigate when clicked.
   *
   * @return string
   */
  public static function render( array $config, bool $interactive = true ): string {
    $defaults = Memberful_Paywall_Config::defaults();
    $config   = wp_parse_args( $config, $defaults );

    // A cleared label is stored as an empty string, which would render a button with no text.
    foreach ( array( 'button_label', 'free_button_label' ) as $key ) {
      if ( '' === trim( (string) $config[ $key ] ) ) {
        $config[ $key ] = $defaults[ $key ];
      }
    }

    $layout = in_array( $config['layout'], Memberful_Paywall_Config::LAYOUTS, true ) ? $config['layout'] : 'card';
    $method = 'render_' . $layout;

    return sprintf(
      '<div class="memberful-paywall memberful-paywall--%1$s" style="%2$s">%3$s</div>',
      esc_attr( $layout ),
      esc_attr( self::wrapper_style( $config ) ),
      self::$method( $config, $interactive )
    );
  }

  /**
   * Render the "inline" layout - minimal text + CTA on a transparent band.
   *
   * @param array $config Sanitized config.
   *
   * @return string
   */
  private static function render_inline( array $config, bool $interactive ): string {
    return '<div class="memberful-paywall__inner">' . self::render_inner( $config, $interactive ) . '</div>';
  }

  /**
   * Render the "card" layout — centred white card with lock badge.
   *
   * @param array $config Sanitized config.
   *
   * @return string
   */
  private static function render_card( array $config, bool $interactive ): string {
    return '<div class="memberful-paywall__inner">'
           . '<div class="memberful-paywall__card">'
           . self::lock_badge()
           . self::render_inner( $config, $interactive )
           . '</div>'
           . '</div>';
  }

  /**
   * Shared inner content shared by every layout: heading, subheading, features, CTA, sign-in.
   *
   * @param array $config Sanitized config.
   *
   * @return string
   */
  private static function render_inner( array $config, bool $interactive ): string {
    $cta     = self::primary_cta( $config, $interactive );
    $actions = '' === $cta ? '' : '<div class="memberful-paywall__actions">' . $cta . '</div>';

    return self::meter_block( $config )
           . self::heading_block( $config )
           . self::subheading_block( $config )
           . self::features_block( $config )
           . $actions
           . self::sign_in_prompt( $config, $interactive );
  }

  /**
   * Free-view counter eyebrow, or empty when disabled or the current view is not metered.
   *
   * The same builder markup also fronts the hard paywall on members-only posts, so the counter only renders when a
   * listener on memberful_paywall_free_view_limit returns the applicable limit; the metering module does so when the
   * meter blocked the post under view. A zero limit has nothing to count, so it hides the counter too.
   *
   * @param array $config Sanitized config.
   *
   * @return string
   */
  private static function meter_block( array $config ): string {
    if ( empty( $config['show_counter'] ) ) {
      return '';
    }

    $template = isset( $config['counter_template'] ) ? (string) $config['counter_template'] : '';
    if ( '' === trim( $template ) ) {
      return '';
    }

    /**
     * Filter the free-view limit shown in the paywall counter.
     *
     * Return null (the default) to hide the counter when the current view is not metered. The metering module
     * returns the anonymous or registered limit when the meter blocked the post under view. Only a positive
     * integer renders the counter.
     *
     * @param int|null $limit  Applicable free-view limit, or null when the current view is not metered.
     * @param array    $config Sanitized paywall config.
     */
    $limit = apply_filters( 'memberful_paywall_free_view_limit', null, $config );
    if ( null === $limit || (int) $limit < 1 ) {
      return '';
    }

    return sprintf(
      '<p class="memberful-paywall__meter">%s</p>',
      esc_html( str_replace( '{limit}', number_format_i18n( (int) $limit ), $template ) )
    );
  }

  /**
   * Heading element.
   *
   * @param array $config Sanitized config.
   *
   * @return string
   */
  private static function heading_block( array $config ): string {
    return sprintf(
      '<h2 class="memberful-paywall__heading">%s</h2>',
      esc_html( $config['heading'] )
    );
  }

  /**
   * Subheading paragraph, or empty when blank.
   *
   * @param array $config Sanitized config.
   *
   * @return string
   */
  private static function subheading_block( array $config ): string {
    if ( '' === $config['subheading'] ) {
      return '';
    }

    return sprintf(
      '<p class="memberful-paywall__subheading">%s</p>',
      esc_html( $config['subheading'] )
    );
  }

  /**
   * Feature list with inline check icons, or empty when no features.
   *
   * @param array $config Sanitized config.
   *
   * @return string
   */
  private static function features_block( array $config ): string {
    if ( empty( $config['features'] ) ) {
      return '';
    }

    $items = '';
    foreach ( $config['features'] as $feature ) {
      $items .= '<li>' . self::check_icon() . '<span>' . esc_html( $feature ) . '</span></li>';
    }

    return '<ul class="memberful-paywall__features">' . $items . '</ul>';
  }

  /**
   * Primary CTA, or empty when there is no usable destination.
   *
   * Renders the free-registration upsell instead of the subscribe button when the visitor was blocked by the meter
   * and creating a free account would grant additional views. Falls back to the subscribe button when the free
   * destination resolves to nothing usable.
   *
   * @param array $config Sanitized config.
   *
   * @return string
   */
  private static function primary_cta( array $config, bool $interactive ): string {
    $label = $config['button_label'];
    $url   = self::subscribe_url( $config );

    if ( self::should_show_free_registration_cta() ) {
      $free_url = self::free_registration_url( $config );

      if ( '' !== $free_url ) {
        $label = $config['free_button_label'];
        $url   = $free_url;
      }
    }

    if ( '' === $url ) {
      return '';
    }

    if ( ! $interactive ) {
      return sprintf(
        '<span class="memberful-paywall__button memberful-paywall__button--primary" aria-disabled="true">%s</span>',
        esc_html( $label )
      );
    }

    return sprintf(
      '<a class="memberful-paywall__button memberful-paywall__button--primary" href="%s">%s</a>',
      esc_url( $url ),
      esc_html( $label )
    );
  }

  /**
   * Whether the paywall should upsell a free registration instead of a paid subscription.
   *
   * True only when a logged-out visitor was blocked by the meter on the post under view and free members have a
   * metered allowance of their own. Anonymous and registered allowances are independent, so registering always
   * grants the full registered limit; only a zero registered limit would make the upsell pointless.
   *
   * @return bool
   */
  private static function should_show_free_registration_cta(): bool {
    if ( is_user_logged_in() ) {
      return false;
    }

    if ( ! class_exists( 'Memberful_Metering_Access' ) || ! class_exists( 'Memberful_Metering_Config' ) ) {
      return false;
    }

    $post_id = (int) get_queried_object_id();

    if ( ! $post_id || Memberful_Metering_Access::DECISION_TRIP_METER !== Memberful_Metering_Access::get_current_decision( $post_id ) ) {
      return false;
    }

    $metering = Memberful_Metering_Config::get();

    if ( empty( $metering['enabled'] ) ) {
      return false;
    }

    return (int) $metering['registered_limit'] > 0;
  }

  /**
   * Resolve the free-registration URL, falling back to the Memberful registration page when it resolves to an
   * absolute URL.
   *
   * @param array $config Sanitized config.
   *
   * @return string
   */
  private static function free_registration_url( array $config ): string {
    if ( ! empty( $config['free_button_url'] ) ) {
      return $config['free_button_url'];
    }

    $registration_url = memberful_registration_page_url();

    return wp_parse_url( $registration_url, PHP_URL_HOST ) ? $registration_url : '';
  }

  /**
   * "Already a subscriber? Sign in" text prompt shown under every layout's CTA.
   *
   * @param array $config Sanitized config.
   *
   * @return string
   */
  private static function sign_in_prompt( array $config, bool $interactive ): string {
    if ( ! $interactive ) {
      return sprintf(
        '<p class="memberful-paywall__signin">%s <span class="memberful-paywall__signin-link" aria-disabled="true">%s</span></p>',
        esc_html__( 'Already a subscriber?', 'memberful' ),
        esc_html__( 'Sign in', 'memberful' )
      );
    }

    return sprintf(
      '<p class="memberful-paywall__signin">%s <a class="memberful-paywall__signin-link" href="%s">%s</a></p>',
      esc_html__( 'Already a subscriber?', 'memberful' ),
      esc_url( self::sign_in_url( $config ) ),
      esc_html__( 'Sign in', 'memberful' )
    );
  }

  /**
   * Circular lock badge shown at the top of the card layout.
   *
   * @return string
   */
  private static function lock_badge(): string {
    return '<div class="memberful-paywall__lock" aria-hidden="true">'
           . '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" focusable="false">'
           . '<path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>'
           . '</svg>'
           . '</div>';
  }

  /**
   * Wrapper inline style carrying the brand colour and button radius custom properties.
   *
   * @param array $config Sanitized config.
   *
   * @return string
   */
  private static function wrapper_style( array $config ): string {
    $parts = array( '--memberful-radius:' . self::button_radius( $config['button_shape'] ) );

    $brand_color = isset( $config['brand_color'] ) ? sanitize_hex_color( (string) $config['brand_color'] ) : '';
    if ( ! empty( $brand_color ) ) {
      $parts[] = '--memberful-brand:' . $brand_color;
      $parts[] = '--memberful-brand-text:' . Memberful_Paywall_Color::contrast_text_color( $brand_color );
    }

    $background_color = isset( $config['background_color'] ) ? sanitize_hex_color( (string) $config['background_color'] ) : '';
    if ( ! empty( $background_color ) ) {
      $parts[] = '--memberful-surface:' . $background_color;
      $parts[] = '--memberful-text:' . Memberful_Paywall_Color::contrast_text_color( $background_color );
    }

    return implode( ';', $parts ) . ';';
  }

  /**
   * Map the button-shape enum to a CSS radius.
   *
   * @param string $shape One of the `button_shape` enum values.
   *
   * @return string
   */
  private static function button_radius( string $shape ): string {
    switch ( $shape ) {
      case 'pill':
        return '999px';
      case 'square':
        return '0';
      case 'rounded':
      default:
        return '8px';
    }
  }

  /**
   * Resolve the subscribe URL, falling back to the Memberful registration page when it resolves to an absolute URL.
   *
   * @param array $config Sanitized config.
   *
   * @return string
   */
  private static function subscribe_url( array $config ): string {
    if ( ! empty( $config['subscribe_url'] ) ) {
      return $config['subscribe_url'];
    }

    $registration_url = memberful_registration_page_url();

    return wp_parse_url( $registration_url, PHP_URL_HOST ) ? $registration_url : '';
  }

  /**
   * Resolve the sign-in URL, falling back to the Memberful sign-in endpoint.
   *
   * @param array $config Sanitized config.
   *
   * @return string
   */
  private static function sign_in_url( array $config ): string {
    return ! empty( $config['sign_in_url'] ) ? $config['sign_in_url'] : memberful_sign_in_url();
  }

  /**
   * Inline check-mark SVG used in the features list.
   *
   * @return string
   */
  private static function check_icon(): string {
    return '<svg class="memberful-paywall__check" width="16" height="16" viewBox="0 0 16 16" aria-hidden="true" focusable="false">'
           . '<path fill="currentColor" d="M13.485 4.515a1 1 0 0 0-1.414 0L6.5 10.086 3.929 7.515a1 1 0 1 0-1.414 1.414l3.278 3.278a1 1 0 0 0 1.414 0l6.278-6.278a1 1 0 0 0 0-1.414z"/>'
           . '</svg>';
  }

  /**
   * Whether the builder paywall is the active rendering mode.
   *
   * @return bool
   */
  private static function is_builder_mode(): bool {
    $config = Memberful_Paywall_Config::get();

    return 'builder' === $config['mode'];
  }
}

Memberful_Paywall_Renderer::register();
