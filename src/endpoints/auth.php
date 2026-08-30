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
