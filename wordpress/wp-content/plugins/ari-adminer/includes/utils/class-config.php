<?php
namespace Ari_Adminer\Utils;

define( 'ARIADMINER_CONFIG_SESSION_KEY', 'adminer_config' );

class Config {
    private $key = null;

    protected $options = null;

    function __construct( $options = array() ) {
        $this->init( $options );
    }

    protected function init( $options ) {
        $this->options = new Config_Options( $options );
    }

    public function __get( $name ) {
        return $name && isset( $this->options->$name ) ? $this->options->$name : null;
    }

    public function get_key( $force = false ) {
        if ( ! $force && ! is_null( $this->key ) )
            return $this->key;

        $sig = $this->options->db_host . '|' . $this->options->db_name . '|' . $this->options->db_user;

        return md5( $sig );
    }

    static protected function ensure_session_start() {
        if ( ! session_id() )
            @session_start();
    }

    public function store() {
        self::ensure_session_start();

        $key = $this->get_key();

        if ( ! isset( $_SESSION[ARIADMINER_CONFIG_SESSION_KEY] ) || ! is_array( $_SESSION[ARIADMINER_CONFIG_SESSION_KEY] ) )
            $_SESSION[ARIADMINER_CONFIG_SESSION_KEY] = array();

        $_SESSION[ARIADMINER_CONFIG_SESSION_KEY][$key] = (array)$this->options;
    }

    public function load( $key, $reset = true ) {
        if ( $reset )
            $this->reset();

        self::ensure_session_start();

        if ( $key && ! empty( $_SESSION[ARIADMINER_CONFIG_SESSION_KEY][$key] ) ) {
            $this->init( $_SESSION[ARIADMINER_CONFIG_SESSION_KEY][$key] );
            return true;
        }

        return false;
    }

    static public function clear( $key ) {
        self::ensure_session_start();

        if ( $key && isset( $_SESSION[ARIADMINER_CONFIG_SESSION_KEY][$key] ) )
            unset( $_SESSION[ARIADMINER_CONFIG_SESSION_KEY][$key] );
    }

    static public function clear_all() {
        self::ensure_session_start();

        if ( isset( $_SESSION[ARIADMINER_CONFIG_SESSION_KEY] ) )
            unset( $_SESSION[ARIADMINER_CONFIG_SESSION_KEY] );
    }

    public function reset() {
        $this->key = null;
        $this->options = null;
    }
}
