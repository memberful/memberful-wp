<?php
/*
Plugin Name: Memberful WP
Plugin URI: http://github.com/memberful/memberful-wp
Description: Allows Memberful users to login to WordPress using the Memberful OAuth 2.0 endpoint.
Version: 0.1
Author: Memberful
Author URI: http://memberful.com
License: GPL
*/

if ( ! defined( 'MEMBERFUL_VERSION' ) )
	define( 'MEMBERFUL_VERSION', '0.1' );

if ( ! defined( 'MEMBERFUL_DIR' ) )
	define( 'MEMBERFUL_DIR', dirname( __FILE__ ) );

if ( ! defined( 'MEMBERFUL_URL' ) )
	define( 'MEMBERFUL_URL', plugins_url( '', __FILE__ ) );

if ( ! defined( 'MEMBERFUL_APPS_HOST' ) )
	define( 'MEMBERFUL_APPS_HOST', 'https://apps.memberful.com' );

// Should requests to memberful check the SSL certificate?
define( 'MEMBERFUL_SSL_VERIFY', defined( 'SITE_ENVIRONMENT' ) ? SITE_ENVIRONMENT == 'production' : FALSE );

require_once MEMBERFUL_DIR.'/src/core-ext.php';
require_once MEMBERFUL_DIR.'/src/urls.php';
require_once MEMBERFUL_DIR.'/src/user/map.php';
require_once MEMBERFUL_DIR.'/src/authenticator.php';
require_once MEMBERFUL_DIR.'/src/admin.php';
require_once MEMBERFUL_DIR.'/src/acl.php';
require_once MEMBERFUL_DIR.'/src/activator.php';
require_once MEMBERFUL_DIR.'/src/shortcodes.php';
require_once MEMBERFUL_DIR.'/src/widgets.php';

register_activation_hook( __FILE__, 'memberful_activate' );

/**
 * Get details about a specific member via the API
 *
 * TODO: Clean this mess up.
 */
function memberful_api_member( $member_id ) { 
	$url = memberful_wrap_api_token( memberful_admin_member_url( $member_id, MEMBERFUL_JSON ) );

	$response = wp_remote_get( $url, array( 'sslverify' => MEMBERFUL_SSL_VERIFY ) );

	if ( is_wp_error( $response ) ) { 
		var_dump( $response, $url );
		die();
	}

	if ( $response['response']['code'] != 200 OR ! isset( $response['body'] ) ) { 
		var_dump( $response );
		return new WP_Error( 'memberful_fail', 'Coult not get member info from api' );
	}

	return json_decode( $response['body'] );
}
