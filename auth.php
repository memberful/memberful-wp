<?php

define( 'MEMBERFUL_DIR', dirname( __FILE__ ) );

require_once MEMBERFUL_DIR.'/../../../wp-load.php';

$authenticator = new Memberful_Authenticator;

add_filter( 'authenticate', array( $authenticator, 'init' ), 10, 3 );
add_filter( 'authenticate', array( $authenticator, 'relay_errors' ), 50, 3 );

if ( isset( $_GET['action'] ) && $_GET['action'] == 'logout' ) {
	wp_logout();
	
	$redirect_to = !empty( $_REQUEST['redirect_to'] ) ? $_REQUEST['redirect_to'] : site_url();
} else {
	$credentials = array( 'user_login' => '', 'user_password' => '', 'remember' => true );

	wp_signon( $credentials, is_ssl() );
	
	// Get redirect from session
	if ( isset( $_REQUEST['redirect_to'] ) ) {
		$redirect_to = $_REQUEST['redirect_to'];
	} elseif ( isset( $_COOKIE['memberful_redirect'] ) ) {
		$redirect_to = $_COOKIE['memberful_redirect'];
	} else {
		$redirect_to = memberful_member_url();
	}

	// If redirect_to was specified but a cookie also exists
	if ( isset( $_COOKIE['memberful_redirect'] ) ) {
		setcookie(
			'memberful_redirect',
			$_SERVER['HTTP_REFERER'],
			time() - 3600,
			COOKIEPATH,
			COOKIE_DOMAIN,
			is_ssl(),
			true
		);
	}
}

wp_safe_redirect( $redirect_to );
exit();
