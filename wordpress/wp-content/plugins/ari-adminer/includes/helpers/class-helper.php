<?php
namespace Ari_Adminer\Helpers;

use Ari_Adminer\Utils\Db_Driver as DB_Driver;
use Ari_Adminer\Models\Connections as Connections_Model;

class Helper {
    private static $system_args = array(
        'action',

        'msg',

        'msg_type',

        'noheader',
    );

    private static $themes = null;

    public static function has_access_to_adminer() {
        return is_super_admin( get_current_user_id() ) || current_user_can( ARIADMINER_CAPABILITY_RUN );
    }

    public static function build_url( $add_args = array(), $remove_args = array(), $remove_system_args = true, $encode_args = true ) {
        if ( $remove_system_args ) {
            $remove_args = array_merge( $remove_args, self::$system_args );
        }

        if ( $encode_args )
            $add_args = array_map( 'rawurlencode', $add_args );

        return add_query_arg( $add_args, remove_query_arg( $remove_args ) );
    }

    public static function get_themes() {
        if ( ! is_null( self::$themes ) ) {
            return self::$themes;
        }

        $folders = array();
        $path = ARIADMINER_THEMES_PATH;
        $exclude = array( 'assets' );

        if ( ! ( $handle = @opendir( $path ) ) ) {
            return $folders;
        }

        while ( false !== ( $file = readdir( $handle ) ) ) {
            if ( '.' == $file || '..' == $file || in_array( $file, $exclude ) )
                continue ;

            $is_dir = is_dir( $path . $file );

            if ( ! $is_dir )
                continue ;

            $folders[] = $file;
        }

        self::$themes = $folders;

        return self::$themes;
    }

    public static function resolve_theme_name( $theme ) {
        $themes = self::get_themes();

        if ( ! in_array( $theme, $themes ) )
            $theme = ARIADMINER_THEME_DEFAULT;

        return $theme;
    }

    public static function get_theme_url( $theme = null ) {
        if ( empty( $theme ) ) {
            $theme = Settings::get_option( 'theme' );
        }

        $theme = self::resolve_theme_name( $theme );
        $theme_url = ARIADMINER_THEMES_URL . $theme . '/adminer.css';

        return $theme_url;
    }

    public static function db_type_to_label( $type ) {
        $label = $type;

        switch ( $type ) {
            case DB_Driver::MYSQL:
            case DB_Driver::SERVER:
                $label = __( 'MySQL', 'ari-adminer' );
                break;

            case DB_Driver::SQLITE:
                $label = __( 'SQLite', 'ari-adminer' );
                break;

            case DB_Driver::POSTGRESQL:
                $label = __( 'PostgreSQL', 'ari-adminer' );
                break;
        }

        return $label;
    }

    public static function get_random_string() {
        mt_srand( (float) microtime() * 1000000 );
        $key = mt_rand();

        return md5( $key );
    }

    public static function save_crypt_key( $crypt_key ) {
        if ( ! ( $fh = fopen( ARIADMINER_CONFIG_PATH, 'w+' ) ) ) {
            return false;
        }

        $config = sprintf( ARIADMINER_CONFIG_TMPL, addcslashes( $crypt_key, "'" ) );
        if ( false === fwrite( $fh, $config ) ) {
            return false;
        }

        fclose( $fh );

        return true;
    }

    public static function get_crypt_key() {
        $crypt_key = '';

        if ( defined( 'ARIADMINER_CRYPT_KEY' ) ) {
            $crypt_key = ARIADMINER_CRYPT_KEY;
        } else {
            if ( file_exists( ARIADMINER_CONFIG_PATH ) ) {
                require_once ARIADMINER_CONFIG_PATH;

                if ( defined( 'ARIADMINER_CRYPT_KEY' ) ) {
                    $crypt_key = ARIADMINER_CRYPT_KEY;
                }
            }
        }

        return $crypt_key;
    }

    public static function support_crypt() {
        $crypt_key = self::get_crypt_key();

        if ( strlen( $crypt_key ) === 0 || ! Crypt::support_crypt() ) {
            return false;
        }

        return true;
    }

    public static function re_crypt_passwords( $new_crypt_key, $old_crypt_key ) {
        $connections_model = new Connections_Model(
            array(
                'class_prefix' => 'Ari_Adminer',
            )
        );

        return $connections_model->re_crypt_passwords( $new_crypt_key, $old_crypt_key );
    }
}
