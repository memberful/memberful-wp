<?php
defined( 'ADMINER_WRAPPER_TYPE' ) || die( 'Access denied.' );

$plugin_root_path = dirname( __FILE__ ) . '/../';
$adminer_path = dirname( __FILE__ ) . '/adminer/';

require_once $plugin_root_path . 'libraries/arisoft/loader.php';

Ari_Loader::register_prefix( 'Ari_Adminer', $plugin_root_path . 'includes' );

use Ari_Adminer\Utils\Config as Config;
use Ari\Utils\Request as Request;
use Ari_Adminer\Helpers\Bridge as WP_Adminer_Bridge;

$is_sys_request = Request::exists( 'username' );
$session_key = Request::get_var( 'username' );

$wp_login_url = WP_Adminer_Bridge::sanitize_url( (string)WP_Adminer_Bridge::get_shared_param( 'wp_login_url' ) );

$adminer_config = new Config();
if ( ! $adminer_config->load( $session_key ) ) {
    if ( $is_sys_request ) {
        die(
            WP_Adminer_Bridge::get_terminated_message( $wp_login_url )
        );
    }
}

if ( ! function_exists( 'adminer_object' ) ) {
	function adminer_object() {
        global $adminer_config, $adminer_path;

        $plugins_path = $adminer_path . '/plugins/';
        // Plugins container: https://www.adminer.org/plugins/#use
        require_once $plugins_path . 'plugin.php';

        foreach ( glob( $plugins_path . '*.php' ) as $plugin_file ) {
            require_once $plugin_file;
        }

        $plugins = array(
            new AdminerDatabaseHide(
                array( 'information_schema' )
            ),

            new AdminerDumpBz2,
            new AdminerDumpDate,
            new AdminerDumpJson,
            new AdminerDumpXml,
            new AdminerDumpZip,

            new AdminerFrames,

            new AdminerEnumOption,
            new AdminerFileUpload,
            new AdminerJsonColumn,
            new AdminerSlugify,
            new AdminerTranslation,

            new AdminerEditForeign,
            new AdminerForeignSystem,
//            new AdminerTablesFilter,
        );

        if ( 'server' == $adminer_config->db_type ) {
            $plugins[] = new AdminerDumpAlter;
        }

		class Adminer_Config extends AdminerPlugin {
            private $config;

            function __construct( $config, $plugins ) {
                $this->config = $config;

                parent::__construct( $plugins );
            }

			function name() {
                return $this->config->title;
            }

            function credentials() {
                return array(
                    $this->config->db_host,
                    $this->config->db_user,
                    $this->config->db_pass,
                );
            }

            function database() {
                return $this->config->db_name;
            }

            function login( $login, $password ) {
                global $wp_login_url;

                if ( $login === $this->config->get_key() )
                    return true;
                else
                    die( WP_Adminer_Bridge::get_terminated_message( $wp_login_url ) );
            }

            function loginForm() {
                global $wp_login_url;

                echo WP_Adminer_Bridge::get_terminated_message( $wp_login_url );
            }
		}
		
		return new Adminer_Config( $adminer_config, $plugins );
	}
}

if ( ADMINER_WRAPPER_TYPE == 'editor' )
    chdir( $adminer_path . 'editor' );
else
    chdir( $adminer_path . 'adminer' );

if ( WP_Adminer_Bridge::is_ajax_request() || WP_Adminer_Bridge::is_resource_request() ) {
    if ( ADMINER_WRAPPER_TYPE == 'editor' )
        require_once $adminer_path . 'editor/index.php';
    else
        require_once $adminer_path . 'adminer/index.php';
} else {
    $bridge = new WP_Adminer_Bridge( $adminer_config );

    $content = '';
    register_shutdown_function(function() {
        global $bridge;

        $buffer = ob_get_contents();
        ob_end_clean();

        echo $bridge->prepare_output( $buffer, ADMINER_WRAPPER_TYPE );;
    });
    ob_start(
        function( $buffer, $phase ) {
            global $content;

            $content .= $buffer;

            return '';
        }
    );

    if ( ADMINER_WRAPPER_TYPE == 'editor' )
        require_once $adminer_path . 'editor/index.php';
    else
        require_once $adminer_path . 'adminer/index.php';

    while ( @ob_end_flush() );
    echo $bridge->prepare_output( $content, ADMINER_WRAPPER_TYPE );
}
