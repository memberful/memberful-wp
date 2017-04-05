<?php
namespace Ari_Adminer\Controllers\Connections;

use Ari\Controllers\Ajax as Ajax_Controller;
use Ari_Adminer\Helpers\Helper as Helper;
use Ari\Utils\Request as Request;

class Ajax_Get_Connection extends Ajax_Controller {
    protected function process_request() {
        if ( $this->options->nopriv || ! Helper::has_access_to_adminer() || ! Request::exists( 'connection_id' ) )
            return false;

        $connection_id = Request::get_var( 'connection_id', 0, 'num' );
        $connection_model = $this->model( 'Connection' );

        $entity = $connection_model->get_connection( $connection_id );

        if ( false === $entity )
            return false;

        $data = $entity->to_array(
            array( 'created', 'modified', 'author_id' )
        );

        return $data;
    }
}
