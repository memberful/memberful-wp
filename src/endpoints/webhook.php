<?php

/**
 * Handles POST requests to the webhook endpoint
 */
class Memberful_Wp_Endpoint_Webhook implements Memberful_Wp_Endpoint {

	public function verify_request( $request_method ) {
		return $request_method === 'POST';
	}

	public function process( array $request_params, array $server_params ) {
		$member_id  = NULL;
		$payload = json_decode($this->raw_request_body());

		if ( strpos( $payload->event, 'order' ) !== FALSE ) {
			$member_id = (int) $payload->order->member->id;

			echo 'Processing order webhook for member '.$member_id;
		} elseif ( strpos( $payload->event, 'member' ) !== FALSE ) {
			$member_id = (int) $payload->member->id;

			echo 'Processing member webhook for member '.$member_id;
		} elseif ( strpos( $payload->event, 'subscription.' ) !== FALSE ) {
			$member_id = (int) $payload->subscription->member->id;

			echo 'Processing subscription webhook for member '.$member_id;
		} elseif ( strpos( $payload->event, 'subscription_plan' ) !== FALSE ) {
			memberful_wp_sync_subscription_plans();

			echo 'Syncing subscription plans';
		} elseif ( strpos( $payload->event, 'download' ) !== FALSE ) {
			memberful_wp_sync_downloads();

			echo 'Syncing downloads';
		} else {
			echo 'Ignoring webhook';
		}

		if ( $member_id !== NULL )
			memberful_wp_sync_member_from_memberful( $member_id );
	}

	private function raw_request_body() {
		return file_get_contents( 'php://input' );
	}
}
