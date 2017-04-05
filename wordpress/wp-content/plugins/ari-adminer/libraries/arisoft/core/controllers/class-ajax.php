<?php
namespace Ari\Controllers;

class Ajax extends Controller {
    function __construct( $options = array() ) {
        $this->options = new Ajax_Options( $options );
    }

    public function execute() {
        $ret = array(
            'result' => null,

            'error' => '',

            'errorCode' => 0
        );

        try {
            $result = $this->process_request();

            $ret['result'] = $result;
        } catch (\Exception $ex) {
            $error_code = $ex->getCode();

            if ( empty( $error_code ) )
                $error_code = 500;

            $ret['error'] = $ex->getMessage();
            $ret['errorCode'] = $error_code;
        }

        @header('Content-type: application/json');

        echo json_encode( $ret, $this->options->json_encode_options );

        wp_die();
    }

    protected function process_request() {
        throw new \BadMethodCallException(
            sprintf(
                '%1$s::process_request() method is not implemented.',
                get_class( $this )
            )
        );
    }
}
