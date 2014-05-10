<?php

/**
 * Get details about a specific member via the API
 *
 * TODO: Clean this mess up.
 */
function memberful_api_member( $member_id ) {
	$response = memberful_wp_get_data_from_api(
		memberful_wp_wrap_api_token( memberful_admin_member_url( $member_id, MEMBERFUL_JSON ) ),
		'fetch_member_account_from_admin'
	);

	$response_code = (int) wp_remote_retrieve_response_code( $response );
	$response_body = wp_remote_retrieve_body( $response );

	if ( is_wp_error( $response ) ) {
		echo "Couldn't contact api: ";
		var_dump( $response, $url );
		die();
	}

	if ( 200 !== $response_code OR empty( $response_body ) ) {
		var_dump( $response );
		return new WP_Error( 'memberful_fail', 'Could not get member info from api' );
	}

	return json_decode( $response_body );
}

function memberful_wp_get_data_from_api( $url, $caller ) {
	if ( strpos($url, 'access_token') === NULL && strpos($url, 'auth_token') === NULL ) {
		$url = memberful_wp_wrap_api_token($url);
	}
	$user_agent = 'WordPress/'.$wp_version.' (PHP '.phpversion().') memberful-wp/'.MEMBERFUL_VERSION;
	$request = array(
		'sslverify' => MEMBERFUL_SSL_VERIFY,
		'headers'   => array(
			'User-Agent' => $user_agent,
			'Accept' => 'application/json'
		),
		'timeout'   => 15
	);

	$response = wp_remote_get( $url, $request );

	memberful_wp_instrument_api_call( $url, $request, $response, $caller );
}

function memberful_wp_put_data_to_api_as_json( $url, $caller, $data ) {
	global $wp_version;

	$url        = memberful_wp_wrap_api_token( $url );
	$user_agent = 'WordPress/'.$wp_version.' (PHP '.phpversion().') memberful-wp/'.MEMBERFUL_VERSION;
	$request    = array(
		'method'  => 'PUT',
		'headers' => array(
			'User-Agent' => $user_agent,
			'Content-Type' => 'application/json',
			'Accept' => 'application/json'
		),
		'body' => json_encode( $data ),
		'timeout' => 15,
		'sslverify' => MEMBERFUL_SSL_VERIFY,
	);

	$response = wp_remote_post( $url, $request );

	memberful_wp_instrument_api_call( $url, $request, $response, $caller );

	return $response;
}

function memberful_wp_instrument_api_call( $url, $request, $response, $caller ) {
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
		$error_payload['caller']     = $caller;
		$error_payload['url']        = $url;
		$error_payload['verify_ssl'] = $request['verify_ssl'];

		memberful_wp_record_api_response_error( $error_payload );
	}
}

function memberful_wp_extract_api_error_log_from_wp_error( $wp_error ) {
	return array(
		'status'   => 0,
		'date'     => gmdate('c'),
		'codes'    => $wp_error->get_error_codes(),
		'messages' => $wp_error->get_error_messages(),
	);
}

function memberful_wp_extract_api_error_log_from_response( $response ) {
	$headers = isset( $response['headers'] ) ? $response['headers'] : array();

	return array(
		'status'       => (int) wp_remote_retrieve_response_code( $response ),
		'date'         => gmdate('c'),
		'request_id'   => isset( $headers['x-request-id'] ) ? $headers['x-request-id'] : 'unknown',
		'cache_hit'    => isset( $headers['x-rack-cache'] ) ? $headers['x-rack-cache'] : 'unknown',
		'runtime'      => isset( $headers['x-runtime'] )    ? $headers['x-runtime']    : 'unknown',
		'content_type' => isset( $headers['content-type'])  ? $headers['content-type'] : 'unknown',
	);
}

function memberful_wp_api_error_log() {
	return get_option( 'memberful_error_log', array() );
}

function memberful_wp_record_api_response_error( $new_payload ) {
	$error_log = get_option( 'memberful_error_log', array() );

	// Try not to overload the WP options table with errors!
	$error_log = array_slice( $error_log, 0, 100, TRUE );

	array_unshift( $error_log, $new_payload );

	update_option( 'memberful_error_log', $error_log );
}


