<?php
namespace Ari\Utils;

class Enum {
    static public function exists( $val ) {
        if ( empty( $val ) || ! is_string( $val ) )
            return false;

        $val = strtoupper( $val );

        return defined( get_called_class() . '::' . $val );
    }

    static public function convert( $val ) {
        if ( ! self::exists( $val ) )
            return false;

        $val = strtoupper( $val );

        return constant( get_called_class() . '::' . $val );
    }
}
