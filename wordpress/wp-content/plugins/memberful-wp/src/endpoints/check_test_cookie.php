<?php

class Memberful_Wp_Endpoint_Check_Test_Cookie implements Memberful_Wp_Endpoint {
  public function verify_request() {
    return $_SERVER['REQUEST_METHOD'] === 'GET';
  }

  public function process() {
    if ( isset( $_COOKIE['memberful_cookie_test'] ) ) {
      Memberful_Wp_Reporting::report("Cookies test passed! Everything should work as expected.", "updated");
    } else {
      Memberful_Wp_Reporting::report("Cookies test failed! Memberful plugin will be not able to sign in users to WordPress. Please contact Memberful support.", "error");
    }

    wp_redirect(memberful_wp_plugin_cookies_test_url());
  }
}
