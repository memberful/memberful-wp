<?php
/**
 * Tests for retrying a Memberful sign-in on admin-post.php when the host
 * stripped the state cookie.
 *
 * @package Memberful
 */

/**
 * Class Tests_Stripped_State_Cookie
 */
class Tests_Stripped_State_Cookie extends WP_UnitTestCase {

  /**
   * A state in the format the plugin issues: 32 hex characters.
   */
  const STATE = '5f0b8c1e9d2a4b6c8e0f1a3b5c7d9e1f';

  /**
   * The URL the last wp_redirect() call was sent to.
   *
   * @var string|null
   */
  private $redirected_to = null;

  /**
   * Capture redirects instead of sending them.
   */
  public function set_up() {
    parent::set_up();

    add_filter( 'wp_redirect', array( $this, 'capture_redirect' ) );
  }

  /**
   * Leave no request behind for the next test.
   */
  public function tear_down() {
    $_GET     = array();
    $_REQUEST = array();
    unset( $_COOKIE[ Memberful_Oauth_State::COOKIE_KEY ] );
    set_current_screen( 'front' );

    parent::tear_down();
  }

  /**
   * Record the redirect and stop wp_redirect() from sending a header.
   *
   * @param string $location Where wp_redirect() was asked to go.
   * @return false
   */
  public function capture_redirect( $location ) {
    $this->redirected_to = $location;

    return false;
  }

  /**
   * Set the query args of the current request, slashed the way WordPress
   * slashes them.
   *
   * @param array $args Query args.
   */
  private function request( array $args ) {
    $_GET     = wp_slash( $args );
    $_REQUEST = $_GET;
  }

  /**
   * A callback from Memberful for a sign-in that started on this site.
   */
  private function callback_with_state() {
    $this->request(
      array(
        'memberful_endpoint' => 'auth',
        'code'               => 'abc123',
        'state'              => self::STATE,
        'redirect_to'        => 'https://example.org/members/?a=1&b=2',
      )
    );
  }

  /**
   * The query args of a URL.
   *
   * @param string $url URL.
   * @return array
   */
  private function query_args( $url ) {
    parse_str( (string) wp_parse_url( $url, PHP_URL_QUERY ), $args );

    return $args;
  }

  /**
   * A callback whose state cookie was stripped is sent to admin-post.php
   * with every arg intact, so the auth endpoint still handles it.
   */
  public function test_callback_without_its_state_cookie_is_retried_on_admin_post() {
    $this->callback_with_state();

    $endpoint = new Memberful_Wp_Endpoint_Auth();
    $endpoint->process();

    $this->assertStringStartsWith( admin_url( 'admin-post.php?' ), $this->redirected_to );
    $this->assertSame(
      array(
        'memberful_endpoint' => 'auth',
        'code'               => 'abc123',
        'state'              => self::STATE,
        'redirect_to'        => 'https://example.org/members/?a=1&b=2',
      ),
      $this->query_args( $this->redirected_to )
    );
  }

  /**
   * An encoded redirect_to arrives on admin-post.php exactly as Memberful sent it.
   */
  public function test_encoded_redirect_to_is_forwarded_unchanged() {
    $this->request(
      array(
        'memberful_endpoint' => 'auth',
        'code'               => 'abc123',
        'state'              => self::STATE,
        'redirect_to'        => 'https%3A%2F%2Fexample.org%2Fit%27s%2F',
      )
    );

    $args = $this->query_args( Memberful_Wp_Endpoint_Auth::uncached_url() );

    $this->assertSame( 'https%3A%2F%2Fexample.org%2Fit%27s%2F', $args['redirect_to'] );
  }

  /**
   * A callback whose state cookie arrived is handled where it landed.
   */
  public function test_callback_with_its_state_cookie_is_not_retried() {
    $this->callback_with_state();
    $_COOKIE[ Memberful_Oauth_State::COOKIE_KEY ] = self::STATE;

    $this->assertFalse( Memberful_Wp_Endpoint_Auth::state_cookie_was_stripped() );
  }

  /**
   * A callback without a state, such as one for a sign-in that started on
   * Memberful, doesn't need the cookie.
   */
  public function test_callback_without_a_state_is_not_retried() {
    $this->request(
      array(
        'memberful_endpoint' => 'auth',
        'code'               => 'abc123',
      )
    );

    $this->assertFalse( Memberful_Wp_Endpoint_Auth::state_cookie_was_stripped() );
  }

  /**
   * Starting a sign-in has no code, so it isn't retried.
   */
  public function test_sign_in_start_is_not_retried() {
    $this->request( array( 'memberful_endpoint' => 'auth' ) );

    $this->assertFalse( Memberful_Wp_Endpoint_Auth::state_cookie_was_stripped() );
  }

  /**
   * A callback already on admin-post.php is finished there, never retried again.
   */
  public function test_callback_on_admin_post_is_not_retried_again() {
    $this->callback_with_state();
    set_current_screen( 'dashboard' );

    $this->assertFalse( Memberful_Wp_Endpoint_Auth::state_cookie_was_stripped() );
  }

  /**
   * When admin-post.php is on another host, the browser may not send it
   * the state cookie either, so the callback isn't retried.
   */
  public function test_callback_is_not_retried_when_admin_post_is_on_another_host() {
    $this->callback_with_state();

    $filter = function ( $url ) {
      return str_replace( '://' . wp_parse_url( home_url(), PHP_URL_HOST ), '://admin.example.net', $url );
    };
    add_filter( 'admin_url', $filter );
    $stripped = Memberful_Wp_Endpoint_Auth::state_cookie_was_stripped();
    remove_filter( 'admin_url', $filter );

    $this->assertFalse( $stripped );
  }

  /**
   * On an https site whose admin URL is http, the callback isn't retried,
   * so the authorization code never travels over plain http.
   */
  public function test_callback_is_not_retried_from_https_to_http() {
    $this->callback_with_state();

    $home_filter  = function ( $url ) {
      return set_url_scheme( $url, 'https' );
    };
    $admin_filter = function ( $url ) {
      return set_url_scheme( $url, 'http' );
    };
    add_filter( 'home_url', $home_filter );
    add_filter( 'admin_url', $admin_filter );
    $stripped = Memberful_Wp_Endpoint_Auth::state_cookie_was_stripped();
    remove_filter( 'home_url', $home_filter );
    remove_filter( 'admin_url', $admin_filter );

    $this->assertFalse( $stripped );
  }

  /**
   * A callback carrying someone else's state is still refused once it's
   * retried on admin-post.php without this browser's state cookie.
   */
  public function test_someone_elses_state_is_refused_on_admin_post() {
    $this->factory->user->create(
      array(
        'role'       => 'subscriber',
        'user_email' => 'owner@example.com',
      )
    );
    add_filter( 'pre_http_request', array( $this, 'memberful_api_response' ), 10, 3 );

    $this->callback_with_state();
    set_current_screen( 'dashboard' );

    $this->expectException( 'WPDieException' );
    $this->expectExceptionMessageMatches( '/memberful_oauth_state_mismatch/' );

    $endpoint = new Memberful_Wp_Endpoint_Auth();
    $endpoint->process();
  }

  /**
   * Stand in for Memberful's API: tokens for any code, and a member who
   * shares an email address with an existing, unlinked WordPress user.
   *
   * @param false|array $preempt Whether to preempt the request.
   * @param array       $args    Request arguments.
   * @param string      $url     Request URL.
   * @return array
   */
  public function memberful_api_response( $preempt, $args, $url ) {
    if ( false !== strpos( $url, 'oauth/token' ) ) {
      $body = array(
        'access_token'  => 'access',
        'refresh_token' => 'refresh',
      );
    } else {
      $body = array(
        'member'        => array(
          'id'         => 990002,
          'email'      => 'owner@example.com',
          'username'   => 'member',
          'first_name' => 'Member',
          'last_name'  => 'Example',
          'full_name'  => 'Member Example',
        ),
        'products'      => array(),
        'subscriptions' => array(),
      );
    }

    return array(
      'headers'  => array(),
      'body'     => wp_json_encode( $body ),
      'response' => array(
        'code'    => 200,
        'message' => 'OK',
      ),
      'cookies'  => array(),
      'filename' => null,
    );
  }

  /**
   * Signing out isn't retried on admin-post.php.
   */
  public function test_sign_out_is_not_retried() {
    $this->request(
      array(
        'memberful_endpoint' => 'auth',
        'action'             => 'logout',
        'redirect_to'        => home_url( '/bye/' ),
      )
    );

    $endpoint = new Memberful_Wp_Endpoint_Auth();
    $endpoint->process();

    $this->assertSame( home_url( '/bye/' ), $this->redirected_to );
  }
}
