<?php
/**
 * Development only. Run by the wp-env afterStart lifecycle script through
 * `wp eval-file` to put the Memberful profile widget in the sidebar. Skips
 * the widget when it is already there, because afterStart runs on every
 * `wp-env start` and `wp widget add` would keep adding copies.
 */

$sidebars = get_option( 'sidebars_widgets', array() );
$widgets  = isset( $sidebars['sidebar-1'] ) ? (array) $sidebars['sidebar-1'] : array();

foreach ( $widgets as $widget_id ) {
  if ( strpos( $widget_id, 'memberful_wp_profile_widget-' ) === 0 ) {
    WP_CLI::log( 'Memberful profile widget is already in sidebar-1.' );
    return;
  }
}

WP_CLI::runcommand( 'widget add memberful_wp_profile_widget sidebar-1 1' );
