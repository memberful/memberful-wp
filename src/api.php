<?php

function memberful_wp_instrument_api_response( $response, $caller ) {
	if ( is_wp_error( $response ) )
		return memberful_wp_record_api_response_error(
			$caller,
			memberful_wp_extract_api_error_log_from_wp_error( $response )
		);

	$status_code = (int) wp_remote_retrieve_response_code( $response, $caller );

	if ( $status_code < 200 || $status_code >= 400 )
		return memberful_wp_record_api_response_error( 
			memberful_wp_extract_api_error_log_from_response( $response, $caller )
		);
}

function memberful_wp_extract_api_error_log_from_wp_error( $wp_error, $caller ) {
	return array(
		'caller'   => $caller,
		'status'   => 0,
		'date'     => gmdate('c'),
		'codes'    => $wp_error->get_error_codes(),
		'messages' => $wp_error->get_error_messages(),
	);
}

function memberful_wp_extract_api_error_log_from_response( $response, $caller ) {
	$headers = isset( $response['headers'] ) ? $response['headers'] : array();

	return array(
		'caller'       => $caller,
		'status'       => (int) wp_remote_retrieve_response_code( $response ),
		'date'         => gmdate('c'),
		'request_id'   => isset( $headers['X-Request-Id'] ) ? $headers['X-Request-Id'] : 'unknown',
		'cache_hit'    => isset( $headers['X-Rack-Cache'] ) ? $headers['X-Rack-Cache'] : 'unknown',
		'runtime'      => isset( $headers['X-Runtime'] )    ? $headers['X-Runtime'] : 'unknown',
		'content_type' => isset( $headers['Content-Type'])  ? $headers['Content-Type'] : 'unknown',
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


/**
 * Get details about a specific member via the API
 *
 * TODO: Clean this mess up.
 */
function memberful_api_member( $member_id ) {
	$url = memberful_wp_wrap_api_token( memberful_admin_member_url( $member_id, MEMBERFUL_JSON ) );

	$response	  = wp_remote_get( $url, array( 'sslverify' => MEMBERFUL_SSL_VERIFY ) );
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

function memberful_wp_send_data_to_api_as_json( $url, $caller, $data ) {
	global $wp_version;

	$user_agent = 'WordPress/'.$wp_version.' (PHP '.phpversion().') memberful-wp/'.MEMBERFUL_VERSION;

	$response = wp_remote_post(
		memberful_wp_wrap_api_token( $url ),
		array(
			'method'  => 'PUT',
			'headers' => array(
				'User-Agent' => $user_agent,
				'Content-Type' => 'application/json',
				'Accept' => 'application/json'
			),
			'body' => json_encode( $data ),
			'timeout' => 10,
			'sslverify' => MEMBERFUL_SSL_VERIFY,
		)
	);

	memberful_wp_instrument_api_response( $response, $caller );

	return $response;
}
