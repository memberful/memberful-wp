<?php

class Memberful_Authenticator {
  /**
   * Gets the url for the specified action at the member OAuth endpoint
   *
   * @param string $action Action to access at endpoint
   * @return string URL
   */
  static public function oauth_member_url( $action = '' ) {
    return memberful_url( 'oauth/'.$action );
  }

  /**
   * Authentication for subscribers is handled by Memberful.
   * Prevent subscribers from requesting password resets
   *
   * @return boolean
   */
  static public function audit_password_reset( $allowed, $user_id ) {
    $user = new WP_User( $user_id );
    $member_role = memberful_wp_user_role_for_user( $user );

    return $user->has_cap( $member_role ) ? FALSE : $allowed;
  }

  /**
   * Returns the url of the endpoint that members will be sent to
   *
   * @return string
   */
  static function oauth_auth_url( $redirect_to ) {
    $params = array(
      'response_type' => 'code',
      'client_id'     => get_option( 'memberful_client_id' ),
    );

    if ( $redirect_to ) {
      $params['redirect_to'] = urlencode($redirect_to);
    }

    return add_query_arg( $params, self::oauth_member_url() );
  }

  /**
   * @var WP_Error Errors encountered
   */
  protected $_wp_error = NULL;

  protected function _error( $code, $error = NULL ) {
    $message = array(
      "We had a problem signing you in, please try again later or contact the site admin."
    );

    if ( is_wp_error($error) ) {
      $message = array_merge($message, $error->get_error_messages());
    } elseif ( ! empty($error) ) {
      array_push($message, htmlentities((string) $error, ENT_QUOTES));
    }

    array_push($message, htmlentities($code));

    wp_die(implode('<br/>', $message));
  }

  /**
   * Callback for the `authenticate` hook.
   *
   * @return WP_User The user to be logged in or NULL if user couldn't be
   * determined
   */
  public function init( $user, $username, $password ) {
    // If another authentication system has handled this request
    if ( $user instanceof WP_User ) {
      return $user;
    }

    // This is the OAuth response
    if ( isset( $_GET['code'] ) ) {
      $tokens = $this->get_oauth_tokens( $_GET['code'] );

      $account = $this->get_member_data( $tokens->access_token );

      $lock_timeout = 10;
      $user = memberful_wp_sync_member_account( $account,  array( 'refresh_token' => $tokens->refresh_token ), $lock_timeout );

      if ( is_wp_error( $user ) ) {
        if ( $user->get_error_code() === 'user_already_exists' ) {
          $error_data = $user->get_error_data();

          return $this->ask_user_to_verify_they_want_to_sync_accounts(
            $error_data['existing_user'],
            $error_data['member'],
            $error_data['context']
          );
        } else {
          return $this->_error( 'memberful_oauth_error' );
        }
      }

      return $user;
    } elseif ( isset( $_GET['error'] ) ) {
      return $this->_error('memberful_oauth_error', wp_kses_post($_GET['error']));
    }

    $redirect_to = get_home_url();

    if ( isset( $_SERVER['HTTP_REFERER'] ) ) {
      $redirect_to = wp_sanitize_redirect( $_SERVER['HTTP_REFERER'] );
    }

    // Allow overriding of redirect location
    if ( isset( $_REQUEST['redirect_to'] ) ) {
      $redirect_to = wp_sanitize_redirect( $_REQUEST['redirect_to'] );
    }

    // Send the user to Memberful
    wp_redirect( self::oauth_auth_url( $redirect_to ), 302 );
    exit();
  }


  public function hook_into_wordpress() {
    add_filter( 'authenticate', array( $this, 'init' ), 10, 3 );
  }

  public function ask_user_to_verify_they_want_to_sync_accounts( $existing_wp_user, $memberful_member, array $sync_context ) {
    wp_logout();

    $nonce = Memberful_Sync_Verification::setup( $existing_wp_user, $memberful_member, $sync_context );

    setcookie('memberful_account_link_nonce', $nonce, time()+3600, COOKIEPATH, COOKIE_DOMAIN, false, true);

    wp_safe_redirect(
      add_query_arg( array( 'memberful_account_check' => '1', 'redirect_to' => get_site_url() ), wp_login_url() )
    );
    die();
  }


  /**
   * login_redirect filter
   * Should redirect to where the user came from before he clicked the login button
   */
  public function redirect( $redirect, $request_redirect, $user ) {
    // Not enabled so return default
    if ( ! memberful_wp_oauth_enabled() ) {
      return $redirect;
    }

    return $request_redirect;
  }

  /**
   * Gets the access token and refresh token from an authorization code
   *
   * @param string $auth_code The authorization code returned from OAuth endpoint
   * @return StdObject Access token and Refresh token
   */
  public function get_oauth_tokens( $auth_code ) {
    $params = array(
      'client_id'     => get_option( 'memberful_client_id' ),
      'client_secret' => get_option( 'memberful_client_secret' ),
      'grant_type'    => 'authorization_code',
      'code'          => $auth_code,
    );
    $response = memberful_wp_post_data_to_api_as_json( self::oauth_member_url('token'), $params );

    if ( is_wp_error($response) ) {
      memberful_wp_record_wp_error( $response );
      return $this->_error( 'could_not_get_tokens', $response );
    }

    $body = json_decode( $response['body'] );
    $code = $response['response']['code'];

    if ( $code != 200 || $body === NULL || empty( $body->access_token ) ) {
      $payload = array(
        'code' => 'oauth_access_fail',
        'error' => 'Could not get access token from Memberful',
        'response' => $response
      );

      memberful_wp_record_error( $payload );

      return $this->_error(
        $payload['code'],
        $payload['error']
      );
    }

    return json_decode( $response['body'] );
  }

  /**
   * Gets information about a user from Memberful.
   *
   * @param string $access_token An access token which can be used to get info
   * about the member
   * @return array
   */
  public function get_member_data( $access_token ) {
    $url = memberful_account_url( MEMBERFUL_JSON );

    $response = memberful_wp_get_data_from_api(
      add_query_arg( 'access_token', $access_token, $url ),
      'get_member_data_for_sign_in'
    );

    if ( is_wp_error( $response ) ) {
      return $this->_error( 'fetch_account_connect_failure', $response );
    }

    $body = json_decode( $response['body'] );
    $code = $response['response']['code'];

    if ( $code != 200 OR $body === NULL ) {
      return $this->_error( 'fetch_account_response_failure', 'Could not fetch your data from Memberful. '.$code );
    }

    return $body;
  }
}

class Memberful_Sync_Verification {
  const NONCE_META_KEY   = 'memberful_potential_member_mapping';
  const NONCE_COOKIE_KEY = 'memberful_account_link_nonce';

  public static function setup( $wp_user, $member, array $context = array() ) {
    $verification = new self;

    return $verification->setup_nonce( $wp_user, $member, $context );
  }

  public static function verify( $user, $nonce ) {
    $verification = new self;

    return $verification->confirm_verification( $user, $nonce );
  }

  public function setup_nonce( $wp_user, $member, array $context ) {
    $nonce = $this->get_nonce();

    update_user_meta(
      $wp_user->ID,
      self::NONCE_META_KEY,
      array(
        'nonce'   => $nonce,
        'member'  => $member,
        'context' => (array) $context,
      )
    );

    return $nonce;
  }

  /**
   * Because of the nature of how this works, we'll keep it simple and flexible.
   * @return string
   */
  private function get_nonce() {
    /** In case we'll need to activate something like, we'll have it ready.
    if(function_exists('openssl_random_pseudo_bytes'))
      return bin2hex(openssl_random_pseudo_bytes(32));
    **/

    return wp_create_nonce('memberful_authenticator_nonce');
  }

  public function confirm_verification( $user, $nonce ) {
    if ( $user->has_prop( self::NONCE_META_KEY ) ) {
      $potential_mapping = $user->get( self::NONCE_META_KEY );

      if ( $potential_mapping['member']->email === $user->user_email && $nonce === $potential_mapping['nonce'] ) {
        delete_user_meta( $user->ID, self::NONCE_META_KEY );

        $potential_mapping['context']['user_verified_they_want_to_sync_accounts'] = TRUE;
        $potential_mapping['context']['id_of_user_who_has_verified_the_sync_link'] = (int) $user->ID;

        return memberful_wp_sync_member_from_memberful( $potential_mapping['member']->id, $potential_mapping['context'] );
      }
    }

    return new WP_Error("could_not_verify_sync_link", "We could not verify that this user wanted to link their accounts together");
  }
}

// Backup, prevent members from resetting their password
add_filter( 'allow_password_reset', array( 'Memberful_Authenticator', 'audit_password_reset' ), 50, 2 );
add_filter( 'login_message', 'memberful_wp_display_check_account_message' );
add_filter( 'wp_login', 'memberful_wp_link_accounts_if_appropriate', 10, 2 );
add_action( 'login_form', 'memberful_wp_add_nonce_check_to_login_form' );

function memberful_wp_add_nonce_check_to_login_form() {
  if ( ! isset( $_COOKIE[ Memberful_Sync_Verification::NONCE_COOKIE_KEY ] ) )
    return;

  memberful_wp_render(
    'login_form_nonce_field',
    array(
      'nonce' => $_COOKIE[ Memberful_Sync_Verification::NONCE_COOKIE_KEY ]
    )
  );
}

function memberful_wp_display_check_account_message() {
  if ( isset($_GET['memberful_account_check']) ) {
    $message = apply_filters( 'memberful_account_check_message', __( 'We found an existing account with the same email address. To confirm you own the account, please sign in with your pre-existing password.' ) );
    return '<p>' . $message . '</p>';
  }

  return null;
}

function memberful_wp_link_accounts_if_appropriate($username, $user) {
  if ( isset($_COOKIE[Memberful_Sync_Verification::NONCE_COOKIE_KEY]) ) {
    $cookie_nonce = $_COOKIE[Memberful_Sync_Verification::NONCE_COOKIE_KEY];

    if ( ! empty( $_POST['memberful_wp_confirm_sync_nonce'] ) && $_POST['memberful_wp_confirm_sync_nonce'] === $cookie_nonce ) {
      Memberful_Sync_Verification::verify( $user, $cookie_nonce );
    }

    setcookie(Memberful_Sync_Verification::NONCE_COOKIE_KEY, '', time()-3600, COOKIEPATH, COOKIE_DOMAIN, false, true);
  }
}
