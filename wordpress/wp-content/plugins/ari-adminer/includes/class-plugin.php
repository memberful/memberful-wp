<?php
namespace Ari_Adminer;

use Ari\App\Plugin as Ari_Plugin;
use Ari\Utils\Request as Request;
use Ari_Adminer\Helpers\Settings as Settings;
use Ari_Adminer\Helpers\Screen as Screen;
use Ari_Adminer\Utils\Config as Config;

class Plugin extends Ari_Plugin {
    public function init() {
        if ( Settings::get_option( 'stop_on_logout' ) ) {
            add_action( 'clear_auth_cookie', function() { $this->clear_session(); } );
        }

		if ( ! is_admin() ) 
			return ;
		
        $this->load_translations();

		add_action( 'admin_enqueue_scripts', function() { $this->admin_enqueue_scripts(); } );
		add_action( 'admin_menu', function() { $this->admin_menu(); } );
		add_action( 'admin_init', function() { $this->admin_init(); } );

        parent::init();
    }

    private function load_translations() {
        load_plugin_textdomain( 'ari-adminer', false, ARIADMINER_SLUG . '/languages' );
    }

    private function admin_menu() {
        $pages = array();
        $is_multi_site = is_multisite();
        $settings_cap = $is_multi_site ? 'manage_network_options' : 'manage_options';

        $pages[] = add_menu_page(
            __( 'ARI Adminer', 'ari-adminer' ),
            __( 'ARI Adminer', 'ari-adminer' ),
            ARIADMINER_CAPABILITY_RUN,
            'ari-adminer',
            array( $this, 'display_adminer_runner' ),
            ! ARI_WP_LEGACY ? 'dashicons-admin-tools' : ''
        );

        $pages[] = add_submenu_page(
            'ari-adminer',
            __( 'Run Adminer', 'ari-adminer' ),
            __( 'Run Adminer', 'ari-adminer' ),
            ARIADMINER_CAPABILITY_RUN,
            'ari-adminer-run-adminer',
            array( $this, 'display_adminer_runner' )
        );

        $pages[] = add_submenu_page(
            'ari-adminer',
            __( 'Connections', 'ari-adminer' ),
            __( 'Connections', 'ari-adminer' ),
            ARIADMINER_CAPABILITY_RUN,
            'ari-adminer-connections',
            array( $this, 'display_connections' )
        );

        $pages[] = add_submenu_page(
            'ari-adminer',
            __( 'Settings', 'ari-adminer' ),
            __( 'Settings', 'ari-adminer' ),
            $settings_cap,
            'ari-adminer-settings',
            array( $this, 'display_settings' )
        );

        remove_submenu_page( 'ari-adminer', 'ari-adminer' );

        foreach ( $pages as $page ) {
            add_action( 'load-' . $page, function() {
                Screen::register();
            });
        }
    }

	private function admin_enqueue_scripts() {
		$options = $this->options;

        wp_register_script( 'ari-adminer-app', $options->assets_url . 'common/app.js', array( 'jquery' ), $options->version );
        wp_register_script( 'ari-adminer-app-helper', $options->assets_url . 'common/helper.js', array( 'ari-adminer-app' ), $options->version );
        wp_register_style( 'ari-adminer', $options->assets_url . 'common/css/style.css', array(), $options->version );

        wp_register_script( 'ari-button', $options->assets_url . 'common/button.js', array( 'jquery' ), $options->version );
        wp_register_script( 'ari-modal', $options->assets_url . 'modal/js/modal.js', array( 'jquery' ), $options->version );
        wp_register_style( 'ari-modal', $options->assets_url . 'modal/css/modal.css', array(), $options->version );
	}

    private function admin_init() {
        Settings::init();

        $no_header = (bool) Request::get_var( 'noheader' );

        if ( ! $no_header ) {
            $page = Request::get_var( 'page' );

            if ( 0 === strpos( $page, 'ari-adminer' ) ) {
                ob_start();

                add_action( 'admin_page_' . $page , function() {
                    ob_end_flush();
                }, 99 );
            }
        }
    }

    protected function need_to_update() {
        $installed_version = get_option( ARIADMINER_VERSION_OPTION );

        return ( $installed_version != $this->options->version );
    }

    protected function install() {
        $installer = new \Ari_Adminer\Installer();

        return $installer->run();
    }

    protected function clear_session() {
        Config::clear_all();
    }
}
