<?php
namespace Ari\Utils;

class Array_Helper {
    static public function get_value( $arr, $key, $default = null, $filter = null ) {
        $val = $default;

        if ( isset( $arr[$key] ) ) {
            $val = $arr[$key];

            if ( ! is_null($filter) )
                $val = Filter::filter( $val, $filter );
        }

        return $val;
    }

    static public function to_int( $array, $min = null, $max = null, $unique = true )
    {
        if ( ! is_array( $array ) )
            $array = array($array);

        $int_array = array();
        $check_min = is_int( $min );
        $check_max = is_int( $max );

        foreach ($array as $k => $v) {
            $i = (int)$v;
            if ( ( $check_min && $i < $min ) ||
                ( $check_max && $i > $max ) )
                continue ;

            $int_array[$k] = $i;
        }

        if ( $unique )
            $int_array = array_unique( $int_array );

        return $int_array;
    }

    static public function sort_assoc( $data, $key, $dir = 'asc', $cmp = 'string' ) {
        $sort = new \Ari\Utils\Sort_Utils( $key, $dir, $cmp );
        usort( $data, array( $sort, 'sort' ) );

        return $data;
    }

    static public function ensure_array( $val ) {
        if ( ! is_array( $val ) )
            $val = array( $val );

        return $val;
    }

    static public function is_assoc( $array ) {
        $cnt = is_array( $array ) ? count( $array ) : 0;
        if ( 0 === $cnt )
            return false;

        for ( reset( $array ); is_int( key( $array ) ); next( $array ) );

        return ! is_null( key( $array ) );
    }

    static public function to_flat_array( $params, $delimiter = '$$', $prefix = '' ) {
        $result = array();

        if ( ! is_array( $params ) )
            return $result;

        foreach ( $params as $key => $val ) {
            if ( self::is_assoc( $val ) ) {
                $result = array_merge( $result, self::to_flat_array( $val, $delimiter, $prefix . $key . $delimiter ) );
            } else {
                $result[$prefix . $key] = $val;
            }
        }

        return $result;
    }

    static public function to_complex_array( $params, $delimiter = '$$' ) {
        $result = array();

        if ( ! is_array( $params ) )
            return $result;

        foreach ( $params as $key => $val ) {
            $complex_keys = explode( $delimiter, $key );

            $item = &$result;
            $key_count = count( $complex_keys );
            for ( $i = 0; $i < $key_count - 1; $i++ ) {
                if ( ! isset( $item[$complex_keys[$i]] ) ) {
                    $item[$complex_keys[$i]] = array();
                }

                $item =& $item[$complex_keys[$i]];
            }

            $item[$complex_keys[$key_count - 1]] = $val;
        }

        return $result;
    }

    static public function value_by_path( $path, $src, $separator = '.', $default = null ) {
        $keys = explode( $separator, $path );

        $val =& $src;
        foreach ( $keys as $key ) {
            if ( isset( $val[$key] ) ) {
                $val =& $val[$key];
            } else {
                $val = $default;
                break;
            }
        }

        return $val;
    }

    static public function get_unique_override_parameters( $src, $override ) {
        $unique_params = array();
        if ( is_null( $override ) )
            $override = array();

        foreach ( $src as $src_key => $src_value ) {
            if ( is_array( $src_value ) ) {
                if ( isset( $override[$src_key] ) ) {
                    $sub_params = self::get_unique_override_parameters(
                        $src_value,
                        $override[$src_key]
                    );

                    if ( count( $sub_params ) > 0 )
                        $unique_params[$src_key] = $sub_params;
                }
            } else if ( array_key_exists( $src_key, $override ) ) {
                $override_value = $override[$src_key];

                if ( $override_value != $src_value )
                    $unique_params[$src_key] = $override_value;
            }
        }

        return $unique_params;
    }
}
