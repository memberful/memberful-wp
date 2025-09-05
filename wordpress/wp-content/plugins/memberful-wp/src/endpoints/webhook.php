<?php

/**
 * Handles POST requests to the webhook endpoint
 */
class Memberful_Wp_Endpoint_Webhook implements Memberful_Wp_Endpoint {

  public function verify_request() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      return false;
    }
    
    $webhook_secret = get_option('memberful_webhook_secret');
    if (empty($webhook_secret)) {
      return false;
    }
    
    $signature = $_SERVER['HTTP_X_MEMBERFUL_WEBHOOK_DIGEST'] ?? '';
    if (empty($signature)) {
      return false;
    }
    
    $payload = file_get_contents('php://input');
    $expected_signature = hash('sha256', $payload . $webhook_secret);
    
    return hash_equals($expected_signature, $signature);
  }

  public function process() {
    header("Content-Type: text/plain");

    // Verify the request is authenticated
    if (!$this->verify_request()) {
      http_response_code(401);
      die('Unauthorized');
    }

    $member_id  = NULL;
    $payload = json_decode($this->raw_request_body());

    if ( strpos( $payload->event, 'order' ) !== FALSE ) {
      if (isset($payload->order->member)) {
        $member_id = (int) $payload->order->member->id;
      }

      echo 'Processing order webhook for member '.intval($member_id);
    } elseif ( strpos( $payload->event, 'member' ) !== FALSE ) {
      $member_id = (int) $payload->member->id;

      echo 'Processing member webhook for member '.intval($member_id);
    } elseif ( strpos( $payload->event, 'subscription.' ) !== FALSE ) {
      $member_id = (int) $payload->subscription->member->id;

      echo 'Processing subscription webhook for member '.intval($member_id);
    } elseif ( strpos( $payload->event, 'subscription_plan' ) !== FALSE ) {
      memberful_wp_sync_subscription_plans();

      echo 'Syncing subscription plans';
    } elseif ( strpos( $payload->event, 'download' ) !== FALSE ) {
      memberful_wp_sync_products();

      echo 'Syncing downloads';
    } elseif ( strpos( $payload->event, 'feed' ) !== FALSE ) {
      memberful_wp_sync_products();

      echo 'Syncing feeds';
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
