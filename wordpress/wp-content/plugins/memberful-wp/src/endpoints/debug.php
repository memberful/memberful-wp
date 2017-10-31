<?php

class Memberful_Wp_Endpoint_Debug implements Memberful_Wp_Endpoint {
  public function verify_request( $request_method ) {
    if(!is_ssl())
      return false;

    if($request_method !== "GET")
      return false;

    $memberful_api_key = get_option( "memberful_api_key" );

    if( empty( $memberful_api_key ) )
      return false;

    $headers = getallheaders();

    if(!isset( $headers["X-Memberful-Api-Key"] ))
      return false;

    if($headers["X-Memberful-Api-Key"] != $memberful_api_key)
      return false;

    return true;
  }

  public function process( array $request_params, array $server_params ) {
    $mapping_stats = new Memberful_User_Map_Stats(Memberful_User_Mapping_Repository::table());
    $unmapped_users = $mapping_stats->unmapped_users();

    if( count($unmapped_users) > 0 ) {
      echo "Unmapped users:\n";
      echo str_pad('WP ID', 6), ' ', str_pad('Email', 30), ' ', "Date registered\n";
      foreach($unmapped_users as $unmapped_user) {
        echo str_pad($unmapped_user->ID, 6), ' ', str_pad($unmapped_user->user_email, 30), ' ', $unmapped_user->user_registered, "\n";
      }
      echo "\n";
    }

    echo "Error log:\n";
    var_export( memberful_wp_error_log() );
    exit;
  }
}
