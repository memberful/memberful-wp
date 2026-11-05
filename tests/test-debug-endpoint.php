<?php
/**
 * Tests for the debug endpoint the Memberful app reads a site's diagnostics from.
 *
 * @package Memberful
 */

/**
 * Class Tests_Debug_Endpoint
 */
class Tests_Debug_Endpoint extends WP_UnitTestCase {

  const API_KEY = 'test-api-key';

  /**
   * $_SERVER before the test, restored in tear_down().
   *
   * @var array
   */
  private $original_server;

  /**
   * Set up an HTTPS GET request on a connected site.
   */
  public function set_up() {
    parent::set_up();

    $this->original_server = $_SERVER;

    update_option( 'memberful_api_key', self::API_KEY );

    $_SERVER['HTTPS']          = 'on';
    $_SERVER['REQUEST_METHOD'] = 'GET';
  }

  /**
   * Leave no request state behind for the next test.
   */
  public function tear_down() {
    $_SERVER = $this->original_server;

    remove_filter( 'nocache_headers', array( 'Memberful_Wp_Endpoint_Debug', 'filter_nocache_headers' ), PHP_INT_MAX );

    parent::tear_down();
  }

  /**
   * Whether the endpoint accepts the current request.
   *
   * @return bool|array
   */
  private function verify() {
    $endpoint = new Memberful_Wp_Endpoint_Debug();

    return $endpoint->verify_request();
  }

  /**
   * A rejected request is marked uncacheable too, so a cache can't serve its 400 to the app.
   */
  public function test_rejected_request_is_marked_uncacheable() {
    unset( $_SERVER['HTTPS'] );

    $this->assertFalse( $this->verify() );

    $headers = wp_get_nocache_headers();

    $this->assertSame( 'no-cache, must-revalidate, max-age=0, no-store, private', $headers['Cache-Control'] );
    $this->assertSame( 'no-store', $headers['CDN-Cache-Control'] );
    $this->assertSame( 'no-store', $headers['Cloudflare-CDN-Cache-Control'] );
    $this->assertSame( 'no-cache', $headers['X-LiteSpeed-Cache-Control'] );
  }

  /**
   * A request with the site's API key is accepted.
   */
  public function test_accepts_the_api_key_header() {
    $_SERVER['HTTP_X_MEMBERFUL_API_KEY'] = self::API_KEY;

    $this->assertTrue( $this->verify() );
  }

  /**
   * A request with a wrong API key is rejected.
   */
  public function test_rejects_a_wrong_api_key() {
    $_SERVER['HTTP_X_MEMBERFUL_API_KEY'] = 'wrong';

    $this->assertFalse( $this->verify() );
  }

  /**
   * A request without the API key is rejected.
   */
  public function test_rejects_a_request_without_the_api_key() {
    unset( $_SERVER['HTTP_X_MEMBERFUL_API_KEY'] );

    $this->assertFalse( $this->verify() );
  }

  /**
   * An empty key doesn't match a site that has none configured.
   */
  public function test_rejects_an_empty_key_when_none_is_configured() {
    delete_option( 'memberful_api_key' );
    $_SERVER['HTTP_X_MEMBERFUL_API_KEY'] = '';

    $this->assertFalse( $this->verify() );
  }

  /**
   * The key is only accepted over HTTPS.
   */
  public function test_rejects_a_request_over_http() {
    unset( $_SERVER['HTTPS'] );
    $_SERVER['HTTP_X_MEMBERFUL_API_KEY'] = self::API_KEY;

    $this->assertFalse( $this->verify() );
  }

  /**
   * A POST request is rejected.
   */
  public function test_rejects_a_post_request() {
    $_SERVER['REQUEST_METHOD']           = 'POST';
    $_SERVER['HTTP_X_MEMBERFUL_API_KEY'] = self::API_KEY;

    $this->assertFalse( $this->verify() );
  }
}
