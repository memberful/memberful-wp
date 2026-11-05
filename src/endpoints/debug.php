<?php

class Memberful_Wp_Endpoint_Debug implements Memberful_Wp_Endpoint {
  public function verify_request() {
    self::prevent_caching();

    if(!is_ssl())
      return false;

    if($_SERVER['REQUEST_METHOD'] !== "GET")
      return false;

    $memberful_api_key = get_option( "memberful_api_key" );

    if( empty( $memberful_api_key ) )
      return false;

    $headers = getallheaders();

    if(!isset( $headers["X-Memberful-Api-Key"] ))
      return false;

    if($headers["X-Memberful-Api-Key"] != $memberful_api_key)
      return false;

    return true;
  }

  public function process() {
    // Again, because the endpoint dispatcher sends Cache-Control: private before this runs.
    self::prevent_caching();

    ob_start();
    memberful_wp_debug( TRUE );
    print html_entity_decode( strip_tags( ob_get_clean() ), ENT_QUOTES );
    exit;
  }

  /**
   * The response holds member data and the site's config and error log, so caches must not store it.
   */
  public static function prevent_caching() {
    if ( ! defined( 'DONOTCACHEPAGE' ) ) {
      define( 'DONOTCACHEPAGE', true );
    }

    // Latest priority, so earlier filters can't drop these headers.
    add_filter( 'nocache_headers', array( __CLASS__, 'filter_nocache_headers' ), PHP_INT_MAX );
    nocache_headers();
  }

  /**
   * Tell each cache, in the header it reads, not to store the response.
   *
   * @param array $headers Header names and values.
   * @return array
   */
  public static function filter_nocache_headers( $headers ) {
    // WordPress before 6.8 leaves no-store and private out of Cache-Control for logged-out visitors.
    $headers['Cache-Control'] = 'no-cache, must-revalidate, max-age=0, no-store, private';

    // A host can rewrite Cache-Control outside PHP and still pass these through. Cloudflare honors
    // its own header, and other CDNs read CDN-Cache-Control (RFC 9213).
    $headers['CDN-Cache-Control']            = 'no-store';
    $headers['Cloudflare-CDN-Cache-Control'] = 'no-store';

    // LiteSpeed's server cache obeys this header over Cache-Control and over any rewrite rule that
    // turns caching on.
    $headers['X-LiteSpeed-Cache-Control'] = 'no-cache';

    return $headers;
  }
}
