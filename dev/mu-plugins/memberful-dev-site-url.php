<?php
/**
 * Plugin Name: Memberful dev site URL
 * Description: Development only. Serves the site at http://wordpress.localhost through puma-dev instead of http://wordpress.localhost:8888. Mounted into wp-content/mu-plugins by .wp-env.override.json, never shipped with the plugin.
 *
 * wp-env always appends its port to WP_HOME and WP_SITEURL, and WordPress
 * applies those constants through the option_home and option_siteurl filters.
 * puma-dev proxies http://wordpress.localhost to the wp-env port, so drop the
 * port from both URLs after WordPress has applied the constants.
 */

add_filter( 'option_home', 'memberful_dev_strip_port', 20 );
add_filter( 'option_siteurl', 'memberful_dev_strip_port', 20 );

function memberful_dev_strip_port( $url ) {
  return preg_replace( '#^(https?://[^/:]+):\d+#', '$1', $url );
}
