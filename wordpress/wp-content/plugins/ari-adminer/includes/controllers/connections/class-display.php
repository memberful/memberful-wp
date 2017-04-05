<?php
namespace Ari_Adminer\Controllers\Connections;

use Ari\Controllers\Display as Display_Controller;
use Ari\Utils\Response as Response;
use Ari\Utils\Request as Request;
use Ari_Adminer\Helpers\Helper as Helper;

class Display extends Display_Controller {
	public function display( $tmpl = null ) {
        if ( Request::exists( 'noheader' ) ) {
            $no_header = (bool)Request::get_var( 'noheader' );

            if ( $no_header ) {
                Response::redirect(
                    Helper::build_url(
                        array(
                            'page' => 'ari-adminer-connections',

                            'filter' => $this->model()->encoded_filter_state(),
                        ),
                        array(
                            'noheader',
                        )
                    )
                );
            }
        }

        $model = $this->model();
        $filter = $model->get_state( 'filter' );
        $count = $model->items_count( $filter );

        $page_num = $filter['page_num'];
        $page_size = $filter['page_size'];
        $pages_count = $count > 0 ? ( $page_size > 0 ? ceil( $count / $page_size ) : 1 ) : 0;

        if ( $pages_count > 0 && $page_num > $pages_count - 1 ) {
            $filter['page_num'] = 0;
            $model->set_state( 'filter', $filter );
        }

		parent::display( $tmpl );
	}
}
