<?php

define('MEMBERFUL_DIR', dirname(__FILE__));

if( $_SERVER['REQUEST_METHOD'] !== 'POST' )
	die('The webhook can only be accessed via POST');

$body = file_get_contents('php://input');

require_once MEMBERFUL_DIR.'/../../../wp-load.php';

$digest = $_SERVER['HTTP_X_MEMBERFUL_WEBHOOK_DIGEST'];

$pinger = new Memberful_Wp_Ping(get_option('memberful_webhook_secret'));

$pinger->handle_ping($body, $digest);
