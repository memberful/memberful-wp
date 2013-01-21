<?php

define( 'MEMBERFUL_DIR', dirname( __FILE__ ) );

if ( $_SERVER['REQUEST_METHOD'] !== 'POST' )
	die( 'The webhook can only be accessed via POST' );

$body = file_get_contents( 'php://input' );

require_once MEMBERFUL_DIR . '/../../../wp-load.php';
require_once MEMBERFUL_DIR . '/src/webhook_ping.php';

$digest = $_SERVER['HTTP_X_MEMBERFUL_WEBHOOK_DIGEST'];

$pinger = new Memberful_Wp_Webhook_Ping( get_option( 'memberful_webhook_secret' ) );

try {
	$pinger->handle_ping( $body, $digest );
} catch (Memberful_Wp_Ping_Invalid_Digest $e) {
	header("Status: 401 Unauthorized");
	echo "Digest could not be validated\n";
	echo $e->getMessage();
	die();
} catch (Memberful_Wp_Ping_Invalid_Payload $e) {
	header('Status: 400 Bad Request');
	echo "Payload could not be parsed\n";
	echo $e->getMessage();
	die();
}

echo "ok";
die();
