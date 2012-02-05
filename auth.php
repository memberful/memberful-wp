<?php

define('MEMBERFUL_DIR', dirname(__FILE__));

require_once MEMBERFUL_DIR.'/../../../wp-load.php';

$authenticator = new Memberful_Authenticator;

add_filter('authenticate', array($authenticator, 'init'), 10, 3);
add_filter('authenticate', array($authenticator, 'relay_errors'), 50, 3);

if($_GET['action'] == 'logout') {
	wp_logout();
	
	$redirect_to = memberful_member_logout_url();
} else {
	wp_signon('', is_ssl());
	$redirect_to = isset($_REQUEST['redirect_to']) ? $_REQUEST['redirect_to'] : get_bloginfo('url');
}

wp_safe_redirect($redirect_to);
exit();