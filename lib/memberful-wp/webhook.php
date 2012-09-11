<?php

/**
 * Handles processing an incoming webhook ping
 */
class Memberful_Wp_Ping {
	protected $secret;

	public function __construct($secret) {
		$this->secret = $secret;
		$this->raw_payload = $raw_payload;
		$this->payload     = json_decode($raw_payload);
	}

	public function handle_ping($raw_payload, $digest) {
		$payload = $this->extract_payload($raw_payload, $digest);

		$event = 'memberful_'.$payload->event;

		do_action($event, $payload);
	}

	protected function extract_payload($raw_payload, $digest) {
		$payload = json_decode($raw_payload);

		$this->validate_digest($raw_payload, $digest);
		$this->validate_json($payload);

		return $payload;
	}

	protected function validate_digest($raw_payload, $digest) {
		$calculated_digest = hash('sha256', $raw_payload, TRUE);

		if ( $calculated_digest !== $digest ) {
			throw new Memberful_Wp_Ping_Invalid_Digest($digest, $calculated_digest);
		}
	}

	protected function validate_json($json) {
		if ( $json === NULL ) {
			throw new Memberful_Wp_Ping_Invalid_Payload($json);
		}
	}

}

add_action('memberful_order_created', 'memberful_wp_hook_order_created');

/**
 * Triggered when a order_created event is received via webhook ping
 */
function memberful_wp_hook_order_created($data) {
	$mapper = Memberful_User_Map($data->order->member);
	$mapper->map();
}
