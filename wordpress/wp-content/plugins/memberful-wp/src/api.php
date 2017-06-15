<?php

global $wp_version;

define( 'MEMBERFUL_API_USER_AGENT', 'WordPress/'.$wp_version.' (PHP '.phpversion().') memberful-wp/'.MEMBERFUL_VERSION );

/**
 * Get details about a specific member via the API
 *
 * TODO: Clean this mess up.
 */
function memberful_api_member( $member_id ) {
  $response = memberful_wp_get_data_from_api( memberful_admin_member_url( $member_id, MEMBERFUL_JSON ) );

  $response_code = (int) wp_remote_retrieve_response_code( $response );
  $response_body = wp_remote_retrieve_body( $response );

  if ( is_wp_error( $response ) ) {
    echo "Couldn't contact api: ";
    var_dump( $response, $url );
    die();
  }

  if ( 200 !== $response_code OR empty( $response_body ) ) {
    return new WP_Error( 'memberful_fail', 'Could not get member info from api' );
  }

  return json_decode( $response_body );
}

function memberful_wp_get_data_from_api( $url ) {
  $url = memberful_wp_wrap_api_token( $url );

  $request = array(
    'sslverify' => MEMBERFUL_SSL_VERIFY,
    'headers'   => array(
      'User-Agent' => MEMBERFUL_API_USER_AGENT,
      'Accept' => 'application/json'
    ),
    'timeout'   => 15
  );

  $response = wp_remote_get( $url, $request );

  return $response;
}

function memberful_wp_post_data_to_api_as_json( $url, $data ) {
  $url        = memberful_wp_wrap_api_token( $url );
  $request    = array(
    'method'  => 'POST',
    'headers' => array(
      'User-Agent' => MEMBERFUL_API_USER_AGENT,
      'Content-Type' => 'application/json',
      'Accept' => 'application/json'
    ),
    'body' => json_encode( $data ),
    'timeout' => 15,
    'sslverify' => MEMBERFUL_SSL_VERIFY,
  );

  $response = wp_remote_post( $url, $request );

  return $response;
}

function memberful_wp_put_data_to_api_as_json( $url, $data ) {
  $url        = memberful_wp_wrap_api_token( $url );
  $request    = array(
    'method'  => 'PUT',
    'headers' => array(
      'User-Agent' => MEMBERFUL_API_USER_AGENT,
      'Content-Type' => 'application/json',
      'Accept' => 'application/json'
    ),
    'body' => json_encode( $data ),
    'timeout' => 15,
    'sslverify' => MEMBERFUL_SSL_VERIFY,
  );

  $response = wp_remote_post( $url, $request );

  return $response;
}
