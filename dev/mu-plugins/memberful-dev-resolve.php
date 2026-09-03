<?php
/**
 * Plugin Name: Memberful dev resolver
 * Description: Development only. Lets the wp-env containers reach a Memberful app running on the Docker host under *.memberful.localhost. Mounted into wp-content/mu-plugins by .wp-env.override.json, never shipped with the plugin.
 *
 * libcurl implements RFC 6761 itself: every hostname under .localhost resolves
 * to loopback, bypassing /etc/hosts and DNS. Inside a container loopback is the
 * container, so requests to apps.memberful.localhost never reach puma-dev on
 * the host. CURLOPT_RESOLVE overrides curl's own lookup, so we pin those hosts
 * to the Docker host gateway. The Host header and TLS SNI are untouched, so
 * puma-dev still routes the request to the Memberful app.
 *
 * Requests to any other host, including memberful.com, are left alone.
 */

add_action( 'http_api_curl', 'memberful_dev_resolve_localhost', 10, 3 );

function memberful_dev_resolve_localhost( $handle, $parsed_args, $url ) {
  $host = parse_url( $url, PHP_URL_HOST );

  if ( ! $host || substr( $host, -20 ) !== '.memberful.localhost' ) {
    return;
  }

  $gateway = memberful_dev_docker_host_gateway();

  if ( ! $gateway ) {
    return;
  }

  curl_setopt( $handle, CURLOPT_RESOLVE, array(
    "{$host}:80:{$gateway}",
    "{$host}:443:{$gateway}",
  ) );
}

function memberful_dev_docker_host_gateway() {
  static $gateway = NULL;

  if ( $gateway === NULL ) {
    // gethostbyname() goes through glibc, which honours the host.docker.internal
    // entry Docker adds to the container. It returns the name unchanged on failure.
    $resolved = gethostbyname( 'host.docker.internal' );
    $gateway  = ( $resolved === 'host.docker.internal' ) ? FALSE : $resolved;
  }

  return $gateway;
}
