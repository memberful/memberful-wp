<?php

/**
 * Handles requests for the plugin's authentication page
 *
 * This endpoint should handle GET requests
 */
class Memberful_Wp_Endpoint_Auth implements Memberful_Wp_Endpoint {

  /**
   * Checks that the request to this endpoint is acceptable
   */
  public function verify_request() {
    return $_SERVER['REQUEST_METHOD'] === 'GET';
  }

  public function process() {
    if ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'logout' ) {
      wp_logout();

      $redirect_to = $this->after_logout_redirect_url();
    } elseif ( self::state_cookie_was_stripped() ) {
      $redirect_to = self::uncached_url();
    } else {
      $credentials = array( 'user_login' => '', 'user_password' => '', 'remember' => true );

      $authenticator = new Memberful_Authenticator;
      $authenticator->hook_into_wordpress();

      $user = wp_signon( $credentials, is_ssl() );
      wp_set_current_user( $user->ID );

      $redirect_to = $this->after_login_redirect_url();
    }

    wp_safe_redirect( $redirect_to );
  }

  /**
   * Hosts like WP Engine cache the OAuth callback and strip its
   * `memberful_oauth_state` cookie before PHP runs.
   *
   * Returns true for a callback that carries a state but no state cookie,
   * when admin-post.php gets the same cookies as the home URL and doesn't
   * drop from https to http. The callback is then retried once on
   * admin-post.php, which hosts don't cache, and never again from there.
   *
   * @return bool
   */
  public static function state_cookie_was_stripped() {
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Memberful's OAuth callback carries no nonce.
    if ( is_admin() || ! isset( $_GET['code'], $_GET['state'] ) || isset( $_COOKIE[ Memberful_Oauth_State::COOKIE_KEY ] ) ) {
      return false;
    }

    $home     = wp_parse_url( home_url( '/' ) );
    $uncached = wp_parse_url( admin_url( 'admin-post.php' ) );

    return isset( $home['scheme'], $home['host'], $uncached['scheme'], $uncached['host'], $uncached['path'] )
      && strtolower( $home['host'] ) === strtolower( $uncached['host'] )
      && 0 === strpos( $uncached['path'], COOKIEPATH )
      && ( 'https' !== $home['scheme'] || 'https' === $uncached['scheme'] );
  }

  /**
   * The URL of admin-post.php with this request's query args.
   *
   * @return string
   */
  public static function uncached_url() {
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Memberful's OAuth callback carries no nonce.
    return add_query_arg( urlencode_deep( wp_unslash( $_GET ) ), admin_url( 'admin-post.php' ) );
  }

  private function after_logout_redirect_url() {
    $url = !empty( $_REQUEST['redirect_to'] ) ? wp_sanitize_redirect( $_REQUEST['redirect_to'] ) : home_url();

    return apply_filters( 'memberful_wp_after_sign_out_url', $url );
  }

  private function after_login_redirect_url() {
    if ( isset( $_REQUEST['redirect_to'] ) ) {
      $url = wp_sanitize_redirect ( $_REQUEST['redirect_to'] );
      $url = preg_match('/^https?%/', $url) ? urldecode($url) : $url;
    } else {
      $url = home_url();
    }

    return apply_filters( 'memberful_wp_after_sign_in_url', $url );
  }
}
