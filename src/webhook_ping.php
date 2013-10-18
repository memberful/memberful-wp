<?php

/**
 * Handles processing an incoming webhook ping
 */
class Memberful_Wp_Webhook_Ping {
	protected $secret;

	public function __construct( $secret ) {
		$this->secret = $secret;
	}

	public function handle_ping( $raw_payload, $digest ) {
		$payload = $this->extract_payload( $raw_payload, $digest );

		$event = 'memberful_event_'.str_replace( '.', '_', $payload->event );

		do_action( $event, $payload );
	}

	protected function extract_payload( $raw_payload, $digest ) {
		$payload = json_decode( $raw_payload );

		$this->validate_digest( $raw_payload, $digest );
		$this->validate_json( $payload );

		return $payload;
	}

	protected function validate_digest( $raw_payload, $digest ) {
		$calculated_digest = hash( 'sha256', $raw_payload.$this->secret );

		if ( $calculated_digest != $digest ) {
			throw new Memberful_Wp_Ping_Invalid_Digest( $digest, $calculated_digest );
		}
	}

	protected function validate_json( $json ) {
		if ( $json === NULL ) {
			throw new Memberful_Wp_Ping_Invalid_Payload( $json );
		}
	}

}

class Memberful_Wp_Ping_Invalid_Digest extends RuntimeException {
	public function __construct( $expected_digest, $calculated_digest ) {
		parent::__construct( "Expected digest ".$expected_digest." got ".$calculated_digest );
	}
}

class Memberful_Wp_Ping_Invalid_Payload extends RuntimeException {

}

add_action( 'memberful_event_order_purchased', 'memberful_wp_hook_order_sync' );
add_action( 'memberful_event_order_completed', 'memberful_wp_hook_order_sync' );
add_action( 'memberful_event_order_refunded',  'memberful_wp_hook_order_sync' );
add_action( 'memberful_event_order_suspended', 'memberful_wp_hook_order_sync' );
add_action( 'memberful_event_member_updated',  'memberful_wp_hook_member_updated' );
add_action( 'memberful_event_product_created', 'memberful_wp_hook_product_sync' );
add_action( 'memberful_event_product_updated', 'memberful_wp_hook_product_sync' );
add_action( 'memberful_event_product_deleted', 'memberful_wp_hook_product_sync' );
add_action( 'memberful_event_subscription_created', 'memberful_wp_hook_subscription_sync' );
add_action( 'memberful_event_subscription_updated', 'memberful_wp_hook_subscription_sync' );
add_action( 'memberful_event_subscription_deleted', 'memberful_wp_hook_subscription_sync' );

/**
 * Triggered when a order_created event is received via webhook ping
 */
function memberful_wp_hook_order_sync( $data ) {
	$mapper = new Memberful_User_Map();
	$user   = $mapper->map( $data->order->member );

	$details = memberful_api_member( $data->order->member->id );

	Memberful_Wp_User_Products::sync( $user->ID, $details->products );
	Memberful_Wp_User_Subscriptions::sync( $user->ID, $details->subscriptions );
}

function memberful_wp_hook_member_updated( $data ) {
	$mapper = new Memberful_User_Map();
	$mapper->map( $data->member );
}

function memberful_wp_hook_product_sync( $data ) {
	memberful_wp_sync_products();
}

function memberful_wp_hook_subscription_sync( $data ) {
	memberful_wp_sync_subscriptions();
}
