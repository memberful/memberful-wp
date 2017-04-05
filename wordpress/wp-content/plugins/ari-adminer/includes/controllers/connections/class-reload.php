<?php
namespace Ari_Adminer\Controllers\Connections;

use Ari\Controllers\Controller as Controller;
use Ari\Utils\Response as Response;
use Ari\Utils\Request as Request;
use Ari_Adminer\Helpers\Helper as Helper;

class Reload extends Controller {
    public function execute() {
        $model = $this->model();
        $params = array(
            'page' => 'ari-adminer-connections',

            'filter' => $model->encoded_filter_state(),
        );

        if ( Request::exists('sub_action') ) {
            $sub_action = Request::get_var( 'sub_action' );

            if ( 'add' == $sub_action ) {
                $params['msg_type'] = ARIADMINER_MESSAGETYPE_SUCCESS;
                $params['msg'] = __( 'The connection is saved successfully', 'ari-adminer' );
            }
        }

        Response::redirect(
            Helper::build_url(
                $params
            )
        );
    }
}
