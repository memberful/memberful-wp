<?php

define('MEMBERFUL_DIR', dirname(__FILE__));

require_once MEMBERFUL_DIR.'/../../../wp-load.php';

$authenticator = new Memberful_Authenticator;

add_filter('authenticate', array($authenticator, 'init'), 10, 3);
add_filter('authenticate', array($authenticator, 'relay_errors'), 50, 3);

if(isset($_GET['action']) && $_GET['action'] == 'logout') {
	wp_logout();
	
	$redirect_to = memberful_member_logout_url();
} else {
	wp_signon('', is_ssl());
	// Get redirect from session
	if(isset($_COOKIE['memberful_redirect'])) {
		$redirect_to = $_COOKIE['memberful_redirect'];
		setcookie(
			"memberful_redirect",
			$_SERVER['HTTP_REFERER'],
			time() - 3600,
			COOKIEPATH,
			COOKIE_DOMAIN,
			is_ssl(),
			true
		);
	} elseif (isset($_REQUEST['redirect_to'])) {
		$redirect_to = $_REQUEST['redirect_to'];
	} else {
		$redirect_to = memberful_member_url();
	}
}

wp_safe_redirect($redirect_to);
exit();