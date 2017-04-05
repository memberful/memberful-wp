<?php
namespace Ari_Adminer\Controllers\Connections;

use Ari\Controllers\Ajax as Ajax_Controller;
use Ari_Adminer\Helpers\Helper as Helper;
use Ari_Adminer\Utils\Dbcheck\Db_Check as DB_Check;
use Ari\Utils\Request as Request;

class Ajax_Test extends Ajax_Controller {
    protected function process_request() {
        if ( $this->options->nopriv || ! Helper::has_access_to_adminer() || ! Request::exists( 'connection' ) )
            return false;

        $connection_data = stripslashes_deep( Request::get_var( 'connection' ) );
        if ( ! is_array( $connection_data ) )
            $connection_data = array();

        $result = DB_Check::check_connection( $connection_data );
        $error = DB_Check::get_last_error();

        return array(
            'result' => $result,

            'error' => $error,
        );
    }
}
