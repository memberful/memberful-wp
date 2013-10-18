<?php

define( 'MEMBERFUL_HTML', NULL );
define( 'MEMBERFUL_JSON', 'json' );


function memberful_sign_in_url() {
	return memberful_wp_endpoint_url( 'auth' );
}

function memberful_sign_out_url() {
	return memberful_url( 'auth/sign_out' );
}

function memberful_activation_url() {
	return MEMBERFUL_APPS_HOST.'/activate-app';
}

function memberful_account_url( $format = MEMBERFUL_HTML ) {
	return memberful_url( 'account', $format );
}

function memberful_account_download_url( $product ) {
	return memberful_url( 'account/downloads/get/'.memberful_wp_extract_id_from_slug( $product ) );
}

function memberful_admin_member_url( $member_id, $format = MEMBERFUL_HTML ) {
	return memberful_url( 'admin/members/'.$member_id, $format );
}

function memberful_admin_products_url( $format = MEMBERFUL_HTML ) {
	return memberful_url( 'admin/products', $format );
}

function memberful_admin_subscriptions_url( $format = MEMBERFUL_HTML ) {
	return memberful_url( 'admin/subscriptions', $format );
}

function memberful_admin_product_url( $product_id, $format = MEMBERFUL_HTML ) {
	return memberful_url( 'admin/products/'.( int) $product_id, $format );
}

function memberful_order_completed_url( $order ) {
	return add_query_arg( 'id', $order, memberful_url( 'orders/completed' ) );
}


/**
 * Generate a URL to the Memberful site
 *
 * @param string $uri    The URI to append
 * @param string $format The requested format
 * @return string URL
 */
function memberful_url( $uri = '', $format = MEMBERFUL_HTML ) {
	$endpoint = '/'.trim( $uri,'/' );

	if ( $format !== MEMBERFUL_HTML ) {
		$endpoint .= '.'.$format;
	}

	return rtrim( get_option( 'memberful_site' ),'/' ).$endpoint;
}

// Private generator methods
// You should not rely on their implementation

/**
 * Determines whether to use HTTP or HTTPS on the frontend.
 *
 * By default WordPress will use the protocol of the current page,
 * which means that if the admin panel is using HTTPS then the
 * members will also use HTTPS.
 *
 * This method checks the "WordPress Address (URL)" option in the admin panel
 * to check whether frontend users should use https.
 *
 * @return string 'http' | 'https'
 */
function memberful_frontend_protocol() {
	if ( strpos( get_option( 'siteurl' ), 'https://' ) === 0 ) {
		return 'https';
	}

	return 'http';
}

function memberful_wp_wrap_api_token( $url ) {
	return add_query_arg( 'auth_token', get_option( 'memberful_api_key' ), $url );
}

/**
 * URL to the OAuth callback
 *
 * @return string
 */
function memberful_wp_oauth_callback_url() {
	return memberful_wp_endpoint_url( 'auth' );
}

/**
 * URL to the Webhook endpoint
 *
 * @return string
 */
function memberful_wp_webhook_url() {
	return memberful_wp_endpoint_url( 'webhook' );
}

function memberful_wp_endpoint_url( $endpoint ) {
	return add_query_arg( array( 'memberful_endpoint' => $endpoint ), site_url( '', memberful_frontend_protocol() ) );
}
