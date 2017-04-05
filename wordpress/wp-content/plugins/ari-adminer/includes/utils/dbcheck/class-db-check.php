<?php
namespace Ari_Adminer\Utils\Dbcheck;

use Ari\Utils\Object_Factory as Object_Factory;

class Db_Check {
    private static $last_error = null;

    static public function check_connection( $options ) {
        self::clear_errors();

        $db_type = isset( $options['type'] ) ? $options['type'] : null;
        if ( 'server' == $db_type )
            $db_type = 'mysql';

        $driver = null;

        if ( $db_type ) {
            $driver = Object_Factory::get_object( $db_type, 'Ari_Adminer\\Utils\\Dbcheck\\Drivers', array( $options ) );
        }

        if ( empty( $driver ) ) {
            self::set_error(
                sprintf(
                    __( 'It is not possible to test connection. "%s" DB driver is not implemented.', 'ari-adminer' ),
                    $db_type
                )
            );
            return false;
        }

        $result = $driver->check_connection();
        if ( ! $result ) {
            self::set_error( $driver->get_last_error() );
        }

        return $result;
    }

    static private function set_error( $error ) {
        self::$last_error = $error;
    }

    static public function get_last_error() {
        return self::$last_error;
    }

    static private function clear_errors() {
        self::$last_error = null;
    }
}
