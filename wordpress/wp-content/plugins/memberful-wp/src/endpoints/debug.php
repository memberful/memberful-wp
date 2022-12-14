<?php

class Memberful_Wp_Endpoint_Debug implements Memberful_Wp_Endpoint {
  public function verify_request() {
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
    ob_start();
    memberful_wp_debug();
    print html_entity_decode( strip_tags( ob_get_clean() ), ENT_QUOTES );
    exit;
  }
}
