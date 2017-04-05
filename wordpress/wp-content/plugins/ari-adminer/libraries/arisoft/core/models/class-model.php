<?php
namespace Ari\Models;

use Ari\Utils\Object as Object_Helper;

class Model {
    private $name = null;

    protected $db = null;

    protected $options = null;

    protected $state = array();

    private $state_loaded = false;

    function __construct( $options = array() ) {
        global $wpdb;

        $this->db = $wpdb;

        if ( isset( $options['state'] ) ) {
            $this->state = $options['state'];
            unset( $options['state'] );
        }

        if ( isset( $options['disable_state_load'] ) ) {
            $this->state_loaded = true;
            unset( $options['disable_state_load'] );
        }

        $this->options = new Model_Options( $options );
    }

    public function name() {
        if ( ! is_null( $this->name ) )
            return $this->name;

        $this->name = Object_Helper::extract_name( $this );

        return $this->name;
    }

    public function entity( $name = null ) {
        if ( is_null( $name ) )
            $name = $this->name();

        $entity_class = $this->options->class_prefix . '\\Entities\\' . $name;
        $entity = new $entity_class( $this->db );

        return $entity;
    }

    public function set_state( $key, $val ) {
        $this->ensure_state();

        $this->state[$key] = $val;
    }

    public function get_state( $key = null, $default = null ) {
        $this->ensure_state();

        if ( is_null( $key ) )
            return $this->state;

        return isset( $this->state[$key] ) ? $this->state[$key] : $default;
    }

    protected function ensure_state() {
        if ( ! $this->state_loaded ) {
            $this->populate_state();

            $this->state_loaded = true;
        }
    }

    protected function populate_state() {

    }

    public function data() {
        return null;
    }
}
