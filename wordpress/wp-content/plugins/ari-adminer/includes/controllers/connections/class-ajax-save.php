<?php
namespace Ari_Adminer\Controllers\Connections;

use Ari\Controllers\Ajax as Ajax_Controller;
use Ari_Adminer\Helpers\Helper as Helper;
use Ari\Utils\Request as Request;

class Ajax_Save extends Ajax_Controller {
    protected function process_request() {
        if ( $this->options->nopriv || ! Helper::has_access_to_adminer() || ! Request::exists( 'connection' ) )
            return false;

        $connection_data = stripslashes_deep( Request::get_var( 'connection' ) );
        $connection_model = $this->model( 'Connection' );

        $entity = $connection_model->save( $connection_data );
        $is_valid = ! empty( $entity );

        return $is_valid;
    }
}
