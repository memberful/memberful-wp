<?php
/*
Plugin Name: Memberful WP
Plugin URI: http://github.com/memberful/memberful-wp
Description: Sell memberships and restrict access to content with WordPress and Memberful.
Version: 1.1.1
Author: Memberful
Author URI: http://memberful.com
License: GPLv2 or later
*/

if ( ! defined( 'MEMBERFUL_VERSION' ) )
	define( 'MEMBERFUL_VERSION', '1.1.1' );

if ( ! defined( 'MEMBERFUL_DIR' ) )
	define( 'MEMBERFUL_DIR', dirname( __FILE__ ) );

if ( ! defined( 'MEMBERFUL_URL' ) )
	define( 'MEMBERFUL_URL', plugins_url( '', __FILE__ ) );

if ( ! defined( 'MEMBERFUL_APPS_HOST' ) )
	define( 'MEMBERFUL_APPS_HOST', 'https://apps.memberful.com' );

// Should requests to memberful check the SSL certificate?
define( 'MEMBERFUL_SSL_VERIFY', defined( 'SITE_ENVIRONMENT' ) ? SITE_ENVIRONMENT == 'production' : FALSE );

require_once MEMBERFUL_DIR . '/src/core-ext.php';
require_once MEMBERFUL_DIR . '/src/urls.php';
require_once MEMBERFUL_DIR . '/src/user/map.php';
require_once MEMBERFUL_DIR . '/src/authenticator.php';
require_once MEMBERFUL_DIR . '/src/admin.php';
require_once MEMBERFUL_DIR . '/src/acl.php';
require_once MEMBERFUL_DIR . '/src/activator.php';
require_once MEMBERFUL_DIR . '/src/shortcodes.php';
require_once MEMBERFUL_DIR . '/src/widgets.php';
require_once MEMBERFUL_DIR . '/src/endpoints.php';
require_once MEMBERFUL_DIR . '/src/marketing_content.php';
require_once MEMBERFUL_DIR . '/src/content_filter.php';
require_once MEMBERFUL_DIR . '/src/entities.php';
require_once MEMBERFUL_DIR . '/vendor/reporting.php';

register_activation_hook( __FILE__, 'memberful_wp_plugin_activate' );

function memberful_wp_plugin_activate() {
	add_option( 'memberful_wp_activation_redirect' , true );
}

/**
 * Get details about a specific member via the API
 *
 * TODO: Clean this mess up.
 */
function memberful_api_member( $member_id ) {
	$url = memberful_wp_wrap_api_token( memberful_admin_member_url( $member_id, MEMBERFUL_JSON ) );

	$response      = wp_remote_get( $url, array( 'sslverify' => MEMBERFUL_SSL_VERIFY ) );
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
