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

		if ( strpos( $payload->event, 'order' ) !== FALSE ) {
			$member = (int) $payload->order->member->id;

			echo 'Processing order webhook for member '.$member;
			$this->sync_member( $member );
		} elseif ( strpos( $payload->event, 'member' ) !== FALSE ) {
			$member = (int) $payload->member->id;

			echo 'Processing member webhook for member '.$member;
			$this->sync_member( $member );
		} else {
			echo 'Ignoring webhook';
		}
	}

	private function sync_member( $member_id ) {
		$account = memberful_api_member( $member_id );

		$mapper = new Memberful_User_Map();
		$user   = $mapper->map( $account->member );

		Memberful_Wp_User_Downloads::sync( $user->ID, $account->products );
		Memberful_Wp_User_Subscriptions::sync( $user->ID, $account->subscriptions );
	}

	private function raw_request_body() {
		return file_get_contents( 'php://input' );
	}
}
