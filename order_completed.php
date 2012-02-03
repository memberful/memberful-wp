<?php

define('MEMBERFUL_DIR', dirname(__FILE__));
require_once MEMBERFUL_DIR.'/../../../wp-load.php';

// Attempt to sign the member in
if( !empty($_GET['code']) )
{
	// Force memberful authenticator into action!
	$_GET['memberful_auth'] = 1;
	var_dump(wp_signon('', is_ssl()));
}

$url = empty($_GET['order']) ? memberful_member_url() : memberful_order_completed_url($_GET['order']);

wp_safe_redirect($url);
exit();