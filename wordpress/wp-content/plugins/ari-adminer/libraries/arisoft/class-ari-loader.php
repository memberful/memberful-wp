<?php
final class Ari_Loader {
	private static $prefix_list = array();
	
	public static function register_prefix( $prefix, $path ) {
		if ( !isset( self::$prefix_list[$prefix] ) )
			self::$prefix_list[$prefix] = array();
		
		self::$prefix_list[$prefix][] = $path;
	}
	
	public static function auto_load( $class ) {
		$class_parts = explode( '\\' , $class );
		
		if ( count($class_parts) < 2 )
			return ;

		$prefix = $class_parts[0];
		
		if ( ! isset( self::$prefix_list[$prefix] ) )
			return ;
		
		array_shift( $class_parts );
		$class_name = array_pop( $class_parts );

		$class_parts = array_map( function( $path ) {
			return strtolower( $path );
		}, $class_parts );
		
		$class_path = join( '/', str_replace( '_', '-', $class_parts ) ) . '/class-' . str_replace( '_', '-', strtolower( $class_name ) ) . '.php';
		
		$path_list = self::$prefix_list[$prefix];
		foreach ( $path_list as $path ) {
			$full_class_path = $path . '/' . $class_path;
			
			if ( file_exists( $full_class_path ) ) {
				require_once $full_class_path;
				break ;
			}
		}
	}

    public static function prepare_name( $name ) {
        $name = str_replace( '-', '_', $name );
        $name_parts = explode( '_', strtolower( $name ) );

        $name_parts = array_map( 'ucfirst', $name_parts );
        $name = join( '_', $name_parts );

        return $name;
    }
}

spl_autoload_register( array( 'Ari_Loader', 'auto_load' ) );
