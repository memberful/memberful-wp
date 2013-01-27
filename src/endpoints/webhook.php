<?php
require_once MEMBERFUL_DIR.'/src/webhook_ping.php';

/**
 * Handles POST requests to the webhook endpoint
 */
class Memberful_Wp_Endpoint_Webhook implements Memberful_Wp_Endpoint {

	public function verify_request( $request_method ) {
		return $request_method === 'POST';
	}

	public function process( array $request_params, array $server_params ) {
		$pinger = new Memberful_Wp_Webhook_Ping( get_option( 'memberful_webhook_secret' ) );

		try {

			$pinger->handle_ping(
				$this->raw_request_body(),
				$this->request_webhook_digest( $server_params )
			);

			echo "ok";

		} catch ( Memberful_Wp_Ping_Invalid_Digest $e ) {
			header("Status: 401 Unauthorized");
			echo "Digest could not be validated\n";
			echo $e->getMessage();
		} catch ( Memberful_Wp_Ping_Invalid_Payload $e ) {
			header('Status: 400 Bad Request');
			echo "Payload could not be parsed\n";
			echo $e->getMessage();
		}
	}

	private function raw_request_body() {
		return file_get_contents( 'php://input' );
	}

	private function request_webhook_digest($server) {
		return $server['HTTP_X_MEMBERFUL_WEBHOOK_DIGEST'];
	}
}