<?php
namespace Ari_Adminer\Controllers\Adminer_Runner;

use Ari\Controllers\Controller as Controller;
use Ari\Utils\Response as Response;
use Ari\Utils\Request as Request;
use Ari\Utils\Array_Helper as Array_Helper;
use Ari_Adminer\Helpers\Helper as Helper;
use Ari_Adminer\Helpers\Settings as Settings;
use Ari_Adminer\Utils\Config as Config;
use Ari_Adminer\Utils\Db_Driver as DB_Driver;
use Ari_Adminer\Helpers\Bridge as WP_Adminer_Bridge;

class Run extends Controller {
    public function execute() {
        if ( ! Helper::has_access_to_adminer() ) {
            $this->redirect_to_dashboard( __( 'You do not have permissions to run Adminer', 'ari-adminer' ) );
        }

        $connection_id = intval( Request::get_var( 'id', 0 ), 10 );

        $adminer_options = array(
            'theme_url' => Helper::get_theme_url(),
        );

        if ( $connection_id > 0 ) {
            // Get saved connection parameters
            $connection_model = $this->model( 'Connection' );
            $connection = $connection_model->get_connection( $connection_id );
            if ( false === $connection ) {
                $this->redirect_to_dashboard( __( 'The selected connection is not available.', 'ari-adminer' ) );
            }

            $adminer_options['db_driver'] = $connection->type;
            $adminer_options['db_host'] = $connection->host;
            $adminer_options['db_name'] = $connection->db_name;
            $adminer_options['db_user'] = $connection->user;
            $adminer_options['db_pass'] = $connection->pass;
        } else if ( 0 === $connection_id) {
            // Connect to WordPress database
            $adminer_options['db_driver'] = DB_Driver::MYSQL;
            $adminer_options['db_host'] = DB_HOST;
            $adminer_options['db_name'] = DB_NAME;
            $adminer_options['db_user'] = DB_USER;
            $adminer_options['db_pass'] = DB_PASSWORD;
        } else {
            $error_msg = __( 'Connection parameters are invalid.', 'ari-adminer' );

            if ( ! Request::exists( 'connection' ) ) {
                $this->redirect_to_dashboard( $error_msg );
            }

            $connection_data = stripslashes_deep( Request::get_var( 'connection' ) );
            if ( ! is_array( $connection_data ) )
                $connection_data = array();

            // Use connection parameters which are entered by user
            $connection_model = $this->model( 'Connection' );
            if ( ! is_array( $connection_data ) || ! $connection_model->validate_connection_params( $connection_data ) ) {
                $this->redirect_to_dashboard( $error_msg );
            }

            $adminer_options['db_driver'] = DB_Driver::convert( Array_Helper::get_value( $connection_data, 'type', 'server' ) );
            $adminer_options['db_host'] = Array_Helper::get_value( $connection_data, 'host', '' );
            $adminer_options['db_name'] = Array_Helper::get_value( $connection_data, 'db_name', '' );
            $adminer_options['db_user'] = Array_Helper::get_value( $connection_data, 'user', '' );
            $adminer_options['db_pass'] = Array_Helper::get_value( $connection_data, 'pass', '' );
        }

        $config = new Config( $adminer_options );
        $config->store();
        $options_key = $config->get_key();

        $url_params = array(
            'username' => $options_key,

            'db' => $config->db_name,
        );

        if ( DB_Driver::MYSQL != $config->db_driver )
            $url_params[$config->db_driver] = '';

        WP_Adminer_Bridge::set_shared_param( 'wp_login_url', admin_url( 'admin.php?page=ari-adminer' ) );

        $app = Settings::get_option( 'mode' );

        $adminer_wrapper_url = ARIADMINER_URL . 'adminer/wrapper_adminer.php';
        if ( 'editor' == $app ) {
            $adminer_wrapper_url = ARIADMINER_URL . 'adminer/wrapper_editor.php';
        }

        if ( count( $url_params ) > 0 ) {
            $adminer_wrapper_url .= '?';

            $params = array();
            foreach ( $url_params as $key => $val ) {
                $params[] = $key . '=' . rawurlencode( $val );
            }

            $adminer_wrapper_url .= join( '&', $params );
        }

        Response::redirect( $adminer_wrapper_url );
    }

    private function redirect_to_dashboard( $message, $message_type = ARIADMINER_MESSAGETYPE_ERROR ) {
        Response::redirect(
            Helper::build_url(
                array(
                    'page' => 'ari-adminer',

                    'msg' => $message,

                    'msg_type' => $message_type,
                ),
                array(
                    'action',

                    'id',
                )
            )
        );
        exit();
    }
}
