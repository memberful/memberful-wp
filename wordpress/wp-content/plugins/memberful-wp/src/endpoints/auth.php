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
  public function verify_request( $request_method ) {
    return $request_method === 'GET';
  }

  public function process( array $request_params, array $server_params ) {
    if ( isset( $request_params['action'] ) && $request_params['action'] == 'logout' ) {
      wp_logout();

      $redirect_to = $this->after_logout_redirect_url( $request_params );
    } else {
      $credentials = array( 'user_login' => '', 'user_password' => '', 'remember' => true );

      $authenticator = new Memberful_Authenticator;
      $authenticator->hook_into_wordpress();

      wp_signon( $credentials, is_ssl() );

      $redirect_to = $this->after_login_redirect_url( $request_params );
    }

    wp_safe_redirect( $redirect_to );
  }

  private function after_logout_redirect_url( $params ) {
    $url = !empty( $params['redirect_to'] ) ? $params['redirect_to'] : home_url();

    return apply_filters( 'memberful_wp_after_sign_out_url', $url );
  }

  private function after_login_redirect_url( $params ) {
    if ( isset( $params['redirect_to'] ) ) {
      $url = $params['redirect_to'];
      $url = preg_match('/^https?%/', $url) ? urldecode($url) : $url;
    } else {
      $url = home_url();
    }

    return apply_filters( 'memberful_wp_after_sign_in_url', $url );
  }
}
