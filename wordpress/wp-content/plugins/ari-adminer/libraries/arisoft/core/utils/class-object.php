<?php
namespace Ari\Utils;

class Object {
    public static function extract_name( $obj ) {
        $class = get_class( $obj );
        $class = explode( '\\', $class );

        return array_pop( $class );
    }

    public static function get_properties( $obj ) {
        $vars = get_object_vars( $obj );

        return $vars;
    }

    public static function get_default_properties( $obj ) {
        return get_class_vars( get_class( $obj ) );
    }
}
