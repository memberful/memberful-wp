<?php
namespace Ari_Adminer\Utils\Dbcheck;

class Driver {
    protected $last_error;

    protected $options;

    function __construct( $options = array() ) {
        $this->options = new Driver_Options( $options );
    }

    public function check_connection() {
        $this->clear_error();

        return $this->check_connection_impl();
    }

    protected function check_connection_impl() {
        return false;
    }

    protected function set_error( $error ) {
        $this->last_error = $error;
    }

    public function get_last_error() {
        return $this->last_error;
    }

    protected function clear_error() {
        $this->last_error = null;
    }
}
