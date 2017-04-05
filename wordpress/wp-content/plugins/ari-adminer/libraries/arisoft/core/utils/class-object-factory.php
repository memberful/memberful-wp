<?php
namespace Ari\Utils;

class Object_Factory {
	static public function get_object( $name, $ns, $params = array() ) {
        $obj = null;
		$name = ucfirst( preg_replace( '/[^A-Z_]/i', '', $name ) );

        if ( empty( $name ) ) {
            return $obj;
        }

		$class_name = $ns . '\\' . $name;

        if ( ! class_exists( $class_name ) ) {
            return $obj;
        }

		if ( count( $params ) == 0 ) {
			$obj = new $class_name();
		} else {
			$reflection = new \ReflectionClass( $class_name );
			$obj = $reflection->newInstanceArgs( $params );
		}

		return $obj;
	}
}
