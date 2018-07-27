<?php

class Memberful_Wp_Endpoint_Debug implements Memberful_Wp_Endpoint {
  public function verify_request( $request_method ) {
    if(!is_ssl())
      return false;

    if($request_method !== "GET")
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

  public function process( array $request_params, array $server_params ) {
    ob_start();
    memberful_wp_debug();
    print html_entity_decode( strip_tags( ob_get_clean() ), ENT_QUOTES );
    exit;
  }
}
