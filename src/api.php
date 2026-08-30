<?php

global $wp_version;

define( 'MEMBERFUL_API_USER_AGENT', 'WordPress/'.$wp_version.' (PHP '.phpversion().') memberful-wp/'.MEMBERFUL_VERSION );

/* Get details about a specific member via the API */
function memberful_api_member( $member_id ) {
  $response = memberful_wp_get_data_from_api( memberful_admin_member_url( $member_id, MEMBERFUL_JSON ) );

  $response_code = (int) wp_remote_retrieve_response_code( $response );
  $response_body = wp_remote_retrieve_body( $response );

  if ( 200 !== $response_code OR empty( $response_body ) ) {
    return new WP_Error( 'memberful_fail', 'Could not get member info from api' );
  }

  return json_decode( $response_body );
}

/* Disconnect the WP integration in Memberful */
function memberful_api_disconnect() {
  $url = memberful_wp_wrap_api_token(memberful_disconnect_url());
  $url = add_query_arg("home_url", home_url(), $url);

  $request = array(
    "method"    => "DELETE",
    "sslverify" => MEMBERFUL_SSL_VERIFY,
    "headers"   => array(
      "User-Agent" => MEMBERFUL_API_USER_AGENT,
      "Accept" => "application/json"
    ),
    "timeout"   => 15
  );

  $response = wp_remote_request( $url, $request );

  memberful_wp_instrument_api_call( $url, $request, $response );

  return $response;
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

  memberful_wp_instrument_api_call( $url, $request, $response );

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

  memberful_wp_instrument_api_call( $url, $request, $response );

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

  memberful_wp_instrument_api_call( $url, $request, $response );

  return $response;
}

function memberful_wp_instrument_api_call( $url, $request, $response ) {
  $error_payload = NULL;

  if ( is_wp_error( $response ) ) {
    $error_payload = memberful_wp_extract_api_error_log_from_wp_error( $response );
  } else {
    $status_code = (int) wp_remote_retrieve_response_code( $response );

    if ( $status_code < 200 || $status_code >= 400 ) {
      $error_payload = memberful_wp_extract_api_error_log_from_response( $response );
    }
  }

  if ( $error_payload !== NULL ) {
    $error_payload['url']       = $url;
    $error_payload['sslverify'] = $request['sslverify'];

    memberful_wp_record_error( $error_payload );
  }
}

function memberful_wp_extract_api_error_log_from_wp_error( $wp_error ) {
  return array(
    'status'   => 0,
    'codes'    => $wp_error->get_error_codes(),
    'messages' => $wp_error->get_error_messages(),
  );
}

function memberful_wp_extract_api_error_log_from_response( $response ) {
  $headers = isset( $response['headers'] ) ? $response['headers'] : array();

  return array(
    'status'       => (int) wp_remote_retrieve_response_code( $response ),
    'request_id'   => isset( $headers['x-request-id'] ) ? $headers['x-request-id'] : 'unknown',
    'cache_hit'    => isset( $headers['x-rack-cache'] ) ? $headers['x-rack-cache'] : 'unknown',
    'runtime'      => isset( $headers['x-runtime'] )    ? $headers['x-runtime']    : 'unknown',
    'content_type' => isset( $headers['content-type'])  ? $headers['content-type'] : 'unknown',
  );
}

function memberful_wp_error_log() {
  $error_log = get_option('memberful_error_log', array());
  return is_array($error_log) ? $error_log : array($error_log);
}

function memberful_wp_record_wp_error( $wp_error ) {
  return memberful_wp_record_error(array(
    'codes'    => $wp_error->get_error_codes(),
    'messages' => $wp_error->get_error_messages(),
    'data'     => $wp_error->get_error_data()
  ));
}

function memberful_wp_record_error( $payload ) {
  $payload['backtrace'] = memberful_get_backtrace_as_string();
  $payload['date'] = gmdate('c');

  return memberful_wp_store_error( $payload );
}

function memberful_get_backtrace_as_string() {
  ob_start();
  debug_print_backtrace();
  return ob_get_clean();
}

function memberful_wp_store_error( $new_payload ) {
  // Try not to overload the WP options table with errors!
  $error_log = array_slice( memberful_wp_error_log(), 0, 99, TRUE );

  array_unshift( $error_log, $new_payload );

  update_option( 'memberful_error_log', $error_log, false );

  return true;
}
