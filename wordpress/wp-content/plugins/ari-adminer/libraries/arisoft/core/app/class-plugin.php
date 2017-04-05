<?php
namespace Ari\App;

use Ari\Utils\Request as Request;

class Plugin {
	protected $options;

	function __construct( $options = array() ) {
		$this->options = new Plugin_Options( $options );

        if ( $this->options->main_file ) {
            if ( did_action( 'activate_' . plugin_basename( $this->options->main_file ) ) == 0 ) {
                register_activation_hook( $this->options->main_file, function() { $this->install(); } );
            } else {
                $this->install();
            }
        }
	}
	
	public function init() {
        $ajax_postfix = strtolower( $this->options->class_prefix );
        if ( is_admin() ) {
            add_action( 'admin_init', function() { if ( $this->need_to_update() ) $this->install(); }, 9 );
        }

        add_action( 'wp_ajax_' . $ajax_postfix, function() { $this->ajax_dispatcher(); } );
        add_action( 'wp_ajax_nopriv_' . $ajax_postfix, function() { $this->ajax_dispatcher( true ); } );
	}

    protected function need_to_update() {
        return false;
    }

    protected function install() {

    }

	protected function ajax_dispatcher( $nopriv = false ) {
        $ctrl = Request::get_var( 'ctrl' );

        if ( empty( $ctrl ) ) {
            wp_die();
        }

        $name_parts = explode( '_', $ctrl );

        if ( count( $name_parts ) < 2 ) {
            wp_die();
        }

        $class_name = \Ari_Loader::prepare_name( $name_parts[0] );
        $ctrl_name = \Ari_Loader::prepare_name( $name_parts[1] );

        $controller_class = $this->options->class_prefix . '\\Controllers\\' . $class_name . '\\Ajax_' . $ctrl_name;
        $ctrl_options = array(
            'class_prefix' => $this->options->class_prefix,

            'domain' => $class_name,

            'path' => $this->options->path,

            'nopriv' => $nopriv,
        );

        $ctrl = new $controller_class( $ctrl_options );
        $ctrl->execute();

        wp_die();
	}
	
	public function __call( $name, $arguments ) {
		$method_not_exists = false;
		$name_parts = explode( '_', $name );
		$cmd_type = $name_parts[0];
		array_shift( $name_parts );

		switch ( $cmd_type ) {
			case 'display':
                $ctrl_name = 'display';
                if ( $this->options->page_prefix ) {
                    $current_page = Request::get_var( 'page' );
                    if ( strpos( $current_page, $this->options->page_prefix ) === 0) {
                        $ctrl_name = Request::get_var( 'action', 'display' );
                    }
                } else {
                    $ctrl_name = Request::get_var( 'action', 'display' );
                }

                if ( strpos( $ctrl_name, '_' ) )
                    $ctrl_name = join(
                        '_',
                        array_map( function( $name ) {
                                return ucfirst( strtolower( $name ) );
                            },
                            explode(
                                '_',
                                $ctrl_name
                            )
                        )
                    );
                else
                    $ctrl_name = ucfirst( strtolower( $ctrl_name ) );

				$class_name = join(
					'_',
					array_map( function( $name ) {
						return ucfirst( strtolower( $name ) );
					}, $name_parts )
				);

				$controller_class = $this->options->class_prefix . '\\Controllers\\' . $class_name . '\\' . $ctrl_name;

                $ctrl_options = array(
                    'class_prefix' => $this->options->class_prefix,

                    'domain' => $class_name,

                    'path' => $this->options->path,

                    'view_path' => $this->options->view_path,
                );

                if ( isset( $arguments[0] ) && is_array( $arguments[0] ) ) {
                    $ctrl_options = array_merge_recursive(
                        $ctrl_options,
                        $arguments[0]
                    );
                }

				$ctrl = new $controller_class( $ctrl_options );
				$ctrl->execute();
				break;
				
			default:
				$method_not_exists = true;
				break;
		}
		
		if ( $method_not_exists )
			throw new \BadMethodCallException(
				sprintf( 'The method \'%1$s\' does not exist.', $name )
			);
	}
}
