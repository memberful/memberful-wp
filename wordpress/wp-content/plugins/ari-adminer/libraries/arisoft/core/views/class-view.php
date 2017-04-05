<?php
namespace Ari\Views;

use Ari\Utils\Filter as Filter;
use Ari\Utils\Object as Object_Helper;

class View {
    protected $model = null;

	protected $options = null;

    protected $data = null;

    protected $path_list = null;

    private $name = null;

	function __construct( $model, $options = array() ) {
        $this->model = $model;
        $this->path_list = new \SplPriorityQueue();

        if ( isset( $options['path'] ) ) {
            $path = $options['path'];
            if ( !is_array( $path ) )
                $path = array( $path );

            foreach ( $path as $path_item ) {
                $this->path_list->insert( $path_item, 0 );
            }

            unset( $options['path'] );
        }

		$this->options = new View_Options( $options );
	}

	public function display( $tmpl = null ) {
		if ( empty( $tmpl ) )
			$tmpl = 'default';
        else
            $tmpl = Filter::filter( $tmpl, 'alphanum' );

        $tmplPath = $this->find_tmpl( $tmpl );

		$this->render( $tmplPath );
	}

    public function show_template( $tmpl, $data = null ) {
        require $tmpl;
    }

    protected function add_path( $path, $priority = 1 ) {
        $this->path_list->insert( $path, $priority );
    }

    protected function find_tmpl( $tmpl ) {
        $tmpl_path = '';
        $tmpl_file = $tmpl . '.php';
        foreach ( $this->path_list as $path ) {
            $tmp_path = $path . '/' . $tmpl_file;

            if ( file_exists( $tmp_path ) ) {
                $tmpl_path = $tmp_path;
                break;
            }
        }

        return $tmpl_path;
    }

    protected function render( $tmpl ) {
        $data = $this->get_data();

        require $tmpl;
    }

    protected function get_data( $reload = false ) {
        if ( ! $reload && ! is_null( $this->data ) )
            return $this->data;

        $this->data = $this->model->data();

        return $this->data;
    }

    public function name() {
        if ( ! is_null( $this->name ) )
            return $this->name;

        $this->name = Object_Helper::extract_name( $this );

        return $this->name;
    }
}
