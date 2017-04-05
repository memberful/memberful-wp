<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) )
    exit();

$queries = array(
    'DROP TABLE IF EXISTS `%1$sariadminer_connections`'
);

function execute_queries( $queries ) {
    global $wpdb;

    foreach ( $queries as $query ) {
        $wpdb->query(
            sprintf(
                $query,
                $wpdb->prefix
            )
        );
    }
}

$config_path = WP_CONTENT_DIR . '/ari-adminer-config.php';

if ( @file_exists( $config_path ) ) {
    @unlink( $config_path );
}

if ( ! is_multisite() ) {
    execute_queries( $queries );
    delete_option( 'ari_adminer' );
    delete_option( 'ari_adminer_settings' );
} else {
    global $wpdb;

    $blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
    $original_blog_id = get_current_blog_id();

    foreach ( $blog_ids as $blog_id )   {
        switch_to_blog( $blog_id );

        execute_queries( $queries );
        delete_option( 'ari_adminer' );
        delete_option( 'ari_adminer_settings' );
    }

    switch_to_blog( $original_blog_id );
}
