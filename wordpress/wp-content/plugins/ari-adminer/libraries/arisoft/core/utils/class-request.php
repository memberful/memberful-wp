<?php
namespace Ari\Utils;

class Request {
    static $root_url = null;

	public static function get_var( $name, $default = null, $filter = null ) {
        $val = $default;

        if ( isset( $_REQUEST[$name] ) ) {
            $val = $_REQUEST[$name];

            if ( ! is_null($filter) )
                $val = Filter::filter( $val, $filter );
        }

		return $val;
	}

    public static function exists( $name ) {
        return isset( $_REQUEST[$name] );
    }

    public static function root_url() {
        if ( ! is_null( self::$root_url ) )
            return self::$root_url;

        $is_SSL = ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' );

        if ( ! $is_SSL && ! empty( $_SERVER['HTTPS'] ) )
            $is_SSL = $_SERVER['HTTPS'] != 'off';

        $port = intval( $_SERVER['SERVER_PORT'], 10);
        $port = ( ( $is_SSL && $port != 443 && $port != 80 ) || ( ! $is_SSL && $port != 80) ) ? $port : 0;
        $protocol = $is_SSL ? 'https' : 'http';

        self::$root_url = $protocol . '://' . $_SERVER['SERVER_NAME'] . ( $port > 0 ? ':' . $port : '' );

        return self::$root_url;
    }

    public static function get_ip() {
        $header_keys = array(
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        );

        foreach ( $header_keys as $key ) {
            if ( array_key_exists( $key, $_SERVER ) ) {
                foreach ( array_map( 'trim', explode( ',', $_SERVER[$key] ) ) as $ip ) {
                    if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) !== false ) {
                        return $ip;
                    }
                }
            }
        }

        return false;
    }

    public static function is_prefetch_request() {
        return
            ( ! empty( $_SERVER['HTTP_X_PURPOSE'] ) && ( 'preview' == strtolower( $_SERVER['HTTP_X_PURPOSE'] ) ) ) ||
            ( ! empty( $_SERVER['HTTP_X_MOZ'] ) && 'prefetch' == strtolower( $_SERVER['HTTP_X_MOZ'] ) );
    }
}
