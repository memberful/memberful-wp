<?php

/**
 * Handles POST requests to the webhook endpoint
 */
class Memberful_Wp_Endpoint_Webhook implements Memberful_Wp_Endpoint {

	public function verify_request( $request_method ) {
		return $request_method === 'POST';
	}

	public function process( array $request_params, array $server_params ) {
		$member  = NULL;
		$payload = json_decode($this->raw_request_body());

		if ( strpos( $payload->event, 'order' ) !== FALSE ) {
			$member = (int) $payload->order->member->id;

			echo 'Processing order webhook for member '.$member;
		} elseif ( strpos( $payload->event, 'member' ) !== FALSE ) {
			$member = (int) $payload->member->id;

			echo 'Processing member webhook for member '.$member;
		} else {
			echo 'Ignoring webhook';
		}

		if ( $member !== NULL )
			memberful_wp_sync_member_from_memberful( $member );
	}

	private function raw_request_body() {
		return file_get_contents( 'php://input' );
	}
}
