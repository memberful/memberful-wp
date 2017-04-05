<?php
namespace Ari\Controllers;

use Ari\Utils\Request as Request;
use Ari\Utils\Object as Object_Helper;

class Controller {
    private $model = null;

	private $name = null;
	
	protected $options = null;
	
	function __construct( $options = array() ) {
		$this->options = new Controller_Options( $options );
	}

    public function execute() {

    }

    public function name() {
		if ( ! is_null( $this->name ) )
			return $this->name;

        $this->name = Object_Helper::extract_name( $this );

		return $this->name;
	}

    protected function model( $name = null, $reload = false ) {
        if ( is_null( $name ) )
            $name = $this->options->domain;

        $is_default_model = ( $name == $this->options->domain );
        if ( ! $reload && $is_default_model && ! is_null( $this->model) )
            return $this->model;

        $model_options = array_merge_recursive(
            $this->options->model_options,
            array(
                'class_prefix' => $this->options->class_prefix,
            )
        );
        $model_class = $this->options->class_prefix . '\\Models\\' . $name;
        $model = new $model_class( $model_options );

        if ( $is_default_model )
            $this->model = $model;

        return $model;
    }

    protected function redirect( $query_args = array(), $remove_query_args = array(), $status = 302 ) {
        $query_args = array_map( 'rawurlencode', $query_args );

        $url =
            Request::root_url() .
            add_query_arg(
                $query_args,
                remove_query_arg(
                    $remove_query_args
                )
            );

        wp_redirect( $url, $status );

        exit;
    }
}