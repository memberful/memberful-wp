<?php
namespace Ari_Adminer\Controllers\Connections;

use Ari\Controllers\Controller as Controller;
use Ari\Utils\Response as Response;
use Ari\Utils\Request as Request;
use Ari_Adminer\Helpers\Helper as Helper;

class Bulk_Delete extends Controller {
    public function execute() {
        $result = false;
        $model = $this->model();

        if ( Request::exists( 'connection_id' ) && Helper::has_access_to_adminer() ) {
            $connection_id = Request::get_var( 'connection_id', array() );
            if ( $connection_id > 0 ) {
                if ( is_array( $connection_id ) && count( $connection_id ) > 0 ) {
                    $result = $model->delete( $connection_id );
                }
            }
        }

        if ( $result ) {
            Response::redirect(
                Helper::build_url(
                    array(
                        'page' => 'ari-adminer-connections',

                        'filter' => $model->encoded_filter_state(),

                        'msg' => __( 'The connection(s) deleted successfully', 'ari-adminer' ),

                        'msg_type' => ARIADMINER_MESSAGETYPE_SUCCESS,
                    )
                )
            );
        } else {
            Response::redirect(
                Helper::build_url(
                    array(
                        'page' => 'ari-adminer-connections',

                        'filter' => $model->encoded_filter_state(),

                        'msg' => __( 'The connection(s) can not be deleted', 'ari-adminer' ),

                        'msg_type' => ARIADMINER_MESSAGETYPE_WARNING,
                    )
                )
            );
        }
    }
}
