<?php
namespace Ari\App;

class Installer {
    protected $options;

    protected $db;

    function __construct( $options = array() ) {
        global $wpdb;

        $this->db = $wpdb;
        $this->options = new Installer_Options( $options );
    }

    public function run() {
        return true;
    }

    protected function run_versions_updates() {
        if ( empty( $this->options->installed_version ) )
            return true;

        $version = $this->options->installed_version;

        $update_method_prefix = 'update_to_';
        $methods = get_class_methods( get_class( $this ) );
        $update_methods = array();

        foreach ( $methods as $method ) {
            if ( strpos( $method, $update_method_prefix ) === 0 ) {
                $method_version = str_replace(
                    array(
                        $update_method_prefix,
                        '_'
                    ),
                    array(
                        '',
                        '.'
                    ),
                    $method
                );

                if ( version_compare( $method_version, $version, '>' ) ) {
                    $update_methods[$method_version] = $method;
                }
            }
        }

        if ( count( $update_methods ) > 0 ) {
            uksort( $update_methods,  'version_compare' );

            foreach ( $update_methods as $update_method ) {
                $result = $this->$update_method();

                if ( false === $result)
                    return false;
            }
        }

        return true;
    }
}
