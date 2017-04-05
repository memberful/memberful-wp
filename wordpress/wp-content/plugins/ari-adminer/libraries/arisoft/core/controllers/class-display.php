<?php
namespace Ari\Controllers;

class Display extends Controller {
    function __construct( $options = array() ) {
        $this->options = new Display_Options( $options );
    }

    public function execute() {
        $this->display();
    }

    protected function display( $tmpl = null ) {
        $view = $this->view();

        $view->display( $tmpl );
    }

    protected function view( $format = 'Html' ) {
        $view_class = $this->options->class_prefix . '\\Views\\' . $this->options->domain . '\\' . $format;
        $view_domain = strtolower( str_replace( '_', '-', $this->options->domain ) );

        $view = new $view_class(
            $this->model(),
            array(
                'path' => $this->options->view_path . $view_domain . '/tmpl',

                'domain' => $view_domain,
            )
        );

        return $view;
    }
}
