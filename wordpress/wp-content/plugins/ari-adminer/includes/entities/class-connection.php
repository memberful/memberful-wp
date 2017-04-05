<?php
namespace Ari_Adminer\Entities;

use Ari\Entities\Entity as Entity;
use Ari_Adminer\Utils\Db_Driver as DB_Driver;
use Ari_Adminer\Helpers\Helper as Helper;
use Ari_Adminer\Helpers\Crypt as Crypt;

class Connection extends Entity {
    public $connection_id;

    public $title = '';

    public $type = 'server';

    public $db_name;

    public $host = '';

    public $user = '';

    public $pass = '';

    public $created = '0000-00-00 00:00:00';
	
	public $modified = '0000-00-00 00:00:00';
	
	public $author_id = 0;

    public $crypt = 0;

    function __construct( $db ) {
        parent::__construct( 'ariadminer_connections', 'connection_id', $db );
    }

    public function bind( $data, $ignore = array() ) {
        $result = parent::bind( $data, $ignore );

        switch ( $this->type ) {
            case DB_Driver::SQLITE:
                $this->host = '';
                $this->user = '';
                $this->pass = '';
                break;
        }

        return $result;
    }

    public function store( $force_insert = false ) {
		$now = current_time( 'mysql', 1 );
        if ( $this->is_new() ) {
            $this->created = $now;
			$this->author_id = get_current_user_id();
        } else {
        	$this->modified = $now;
        }

        $plain_pass = $this->pass;

        if ( strlen( $plain_pass ) > 0 && Helper::support_crypt() ) {
            $crypt_key = Helper::get_crypt_key();

            $this->pass = Crypt::crypt( $plain_pass, $crypt_key );
            $this->crypt = 1;
        }

        $res = parent::store( $force_insert );

        $this->pass = $plain_pass;

        return $res;
    }

    public function load( $keys, $reset = true ) {
        $res = parent::load( $keys, $reset );

        if ( $res ) {
            if ( $this->crypt && strlen( $this->pass ) > 0 && Helper::support_crypt() ) {
                $crypt_key = Helper::get_crypt_key();

                $this->pass = Crypt::decrypt( $this->pass, $crypt_key );
            }
        }

        return $res;
    }

    public function validate() {
        if ( empty( $this->title ) )
            return false;

        if ( ! $this->validate_connection_params() )
            return false;

        return true;
    }

    public function validate_connection_params() {
        if ( empty( $this->db_name ) )
            return false;

        return true;
    }
}
