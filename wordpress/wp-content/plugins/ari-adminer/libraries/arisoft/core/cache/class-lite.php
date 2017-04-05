<?php
namespace Ari\Cache;

class Lite {
    static protected $cache = array();

    public static function set( $key, $val ) {
        self::$cache[$key] = $val;
    }

    public static function get( $key, $default = null ) {
        return self::exists( $key ) ? self::$cache[$key] : $default;
    }

    public static function exists( $key ) {
        return isset( self::$cache[$key] );
    }

    public static function clear( $key ) {
        if ( self::exists( $key ) )
            unset( self::$cache[$key] );
    }
}