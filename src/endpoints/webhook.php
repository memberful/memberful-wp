<?php

/**
 * Handles POST requests to the webhook endpoint
 */
class Memberful_Wp_Endpoint_Webhook implements Memberful_Wp_Endpoint {

	public function verify_request( $request_method ) {
		return $request_method === 'POST';
	}

	public function process( array $request_params, array $server_params ) {
		$payload = json_decode($this->raw_request_body());

		if ( strpos( $payload->event, 'order.' ) === 0 ) {
			$this->sync_member( $payload->order->member->id );
		} elseif ( strpos( $payload->event, 'member.' ) === 0 ) {
			$this->sync_member( $payload->member->id );
		}
	}

	private function sync_member( $member_id ) {
		$account = memberful_api_member( $member__id );

		$mapper = new Memberful_User_Map();
		$user   = $mapper->map( $account->member );

		Memberful_Wp_User_Products::sync( $user->ID, $details->products );
		Memberful_Wp_User_Subscriptions::sync( $user->ID, $details->subscriptions );
	}

	private function raw_request_body() {
		return file_get_contents( 'php://input' );
	}
}
