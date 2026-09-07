<?php

class Memberful_Wp_Endpoint_Set_Test_Cookie implements Memberful_Wp_Endpoint {
  public function verify_request() {
    return $_SERVER['REQUEST_METHOD'] === 'GET';
  }

  public function process() {
    setcookie(
      'memberful_cookie_test',
      'passed',
      time() + 5,
      COOKIEPATH,
      COOKIE_DOMAIN,
      is_ssl(),
      true
    );

    wp_redirect(memberful_wp_endpoint_url('check_test_cookie'));
  }
}
