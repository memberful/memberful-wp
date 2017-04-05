<?php
/*
	Plugin Name: ARI Adminer
	Plugin URI: http://wp-quiz.ari-soft.com/plugins/wordpress-adminer.html
	Description: Powerful, compact and easy to use database manager plugin for WordPress.
	Version: 1.1.0
	Author: ARI Soft
	Author URI: http://www.ari-soft.com
	Text Domain: ari-adminer
	Domain Path: /languages
	License: GPL2
 */

defined( 'ABSPATH' ) or die( 'Access forbidden!' );

define( 'ARIADMINER_EXEC_FILE', __FILE__ );
define( 'ARIADMINER_URL', plugin_dir_url( __FILE__ ) );
define( 'ARIADMINER_PATH', plugin_dir_path( __FILE__ ) );

if ( ! defined( 'ARI_WP_LEGACY' ) ) {
    $current_wp_version = get_bloginfo( 'version' );
    define( 'ARI_WP_LEGACY', version_compare( $current_wp_version, '4.0', '<' ) );
}

if ( ! function_exists( 'ari_adminer_init' ) ) {
    function ari_adminer_init() {
        if ( defined( 'ARIADMINER_INITED' ) )
            return ;

        define( 'ARIADMINER_INITED', true );

        require_once ARIADMINER_PATH . 'includes/defines.php';
        require_once ARIADMINER_PATH . 'libraries/arisoft/loader.php';

        Ari_Loader::register_prefix( 'Ari_Adminer', ARIADMINER_PATH . 'includes' );

        $plugin = new \Ari_Adminer\Plugin(
            array(
                'class_prefix' => 'Ari_Adminer',

                'version' => ARIADMINER_VERSION,

                'path' => ARIADMINER_PATH,

                'url' => ARIADMINER_URL,

                'assets_url' => ARIADMINER_ASSETS_URL,

                'view_path' => ARIADMINER_PATH . 'includes/views/',

                'main_file' => __FILE__,
            )
        );
        $plugin->init();
    }
}

if ( ! function_exists( 'ari_adminer_activation_check' ) ) {
    function ari_adminer_activation_check() {
        $min_php_version = '5.4.0';
        $min_wp_version = '3.4';

        $current_wp_version = get_bloginfo( 'version' );
        $current_php_version = PHP_VERSION;

        $is_supported_php_version = version_compare( $current_php_version, $min_php_version, '>=' );
        $is_spl_installed = function_exists( 'spl_autoload_register' );
        $is_supported_wp_version = version_compare( $current_wp_version, $min_wp_version, '>=' );

        if ( ! $is_supported_php_version || ! $is_spl_installed || ! $is_supported_wp_version ) {
            deactivate_plugins( basename( ARIADMINER_EXEC_FILE ) );

            $recommendations = array();

            if ( ! $is_supported_php_version )
                $recommendations[] = sprintf(
                    __( 'update PHP version on your server from v. %s to at least v. %s', 'ari-adminer' ),
                    $current_php_version,
                    $min_php_version
                );

            if ( ! $is_spl_installed )
                $recommendations[] = __( 'install PHP SPL extension', 'ari-adminer' );

            if ( ! $is_supported_wp_version )
                $recommendations[] = sprintf(
                    __( 'update WordPress v. %s to at least v. %s', 'ari-adminer' ),
                    $current_wp_version,
                    $min_wp_version
                );

            wp_die(
                sprintf(
                    __( '"ARI Adminer" can not be activated. It requires PHP version 5.4.0+ with SPL extension and WordPress 4.0+.<br /><br /><b>Recommendations:</b> %s.<br /><br /><a href="%s" class="button button-primary">Back</a>', 'ari-adminer' ),
                    join( ', ', $recommendations ),
                    get_dashboard_url( get_current_user_id() )
                )
            );
        } else {
            ari_adminer_init();
        }
    }
}

add_action( 'plugins_loaded', 'ari_adminer_init' );
register_activation_hook( ARIADMINER_EXEC_FILE, 'ari_adminer_activation_check' );
