<?php
namespace Ari_Adminer;

use Ari\App\Installer as Ari_Installer;
use Ari\Database\Helper as DB;
use Ari\Wordpress\Security as Security;
use Ari_Adminer\Helpers\Helper as Helper;

class Installer extends Ari_Installer {
    function __construct( $options = array() ) {
        if ( ! isset( $options['installed_version'] ) ) {
            $installed_version = get_option( ARIADMINER_VERSION_OPTION );

            if ( false !== $installed_version) {
                $options['installed_version'] = $installed_version;
            }
        }

        if ( ! isset( $options['version'] ) ) {
            $options['version'] = ARIADMINER_VERSION;
        }

        parent::__construct( $options );
    }

    private function init() {
        $this->add_cap();

        $sql = file_get_contents( ARIADMINER_INSTALL_PATH . 'install.sql' );

        $queries = DB::split_sql( $sql );

        foreach( $queries as $query ) {
            $this->db->query( $query );
        }
    }

    public function run() {
        $this->init();

        if ( ! $this->run_versions_updates() ) {
            return false;
        }

        update_option( ARIADMINER_VERSION_OPTION, $this->options->version );

        $this->ensure_crypt_key();

        return true;
    }

    private function add_cap() {
        if ( is_multisite() )
            return ;

        $roles = Security::get_roles();

        foreach ( $roles as $role ) {
            if ( $role->has_cap( 'manage_options' ) ) {
                $role->add_cap( ARIADMINER_CAPABILITY_RUN );
            }
        }
    }

    private function ensure_crypt_key() {
        $crypt_key = Helper::get_crypt_key();

        if ( strlen( $crypt_key ) > 0 )
            return ;

        $crypt_key = Helper::get_random_string();
        if ( Helper::save_crypt_key( $crypt_key ) ) {
            Helper::re_crypt_passwords( $crypt_key, '' );
        }
    }

    protected function update_to_1_1_0() {
        if ( ! DB::column_exists( '#__ariadminer_connections', 'crypt' ) ) {
            $this->db->query(
                sprintf(
                    'ALTER TABLE `%1$sariadminer_connections` ADD COLUMN `crypt` tinyint(1) unsigned NOT NULL DEFAULT "0"',
                    $this->db->prefix
                )
            );
        }
    }
}
