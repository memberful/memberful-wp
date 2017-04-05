<?php
namespace Ari_Adminer\Views\Adminer_Runner;

use Ari_Adminer\Views\Base as Base;

class Html extends Base {
    public function display( $tmpl = null ) {
        $this->set_title( __( 'ARI Adminer', 'ari-adminer' ) );

        wp_enqueue_style( 'ari-modal' );
        wp_enqueue_script( 'ari-modal' );
        wp_enqueue_script( 'ari-button' );
        wp_enqueue_script( 'ari-adminer-page-runner', ARIADMINER_ASSETS_URL . 'common/pages/adminer_runner.js', array( 'ari-adminer-app', 'ari-modal', 'ari-button' ), ARIADMINER_VERSION );

        parent::display( $tmpl );
    }

    protected function get_app_options() {
        $app_options = array(
            'actionEl' => '#ctrl_action',

            'ajaxUrl' => admin_url( 'admin-ajax.php?action=ari_adminer' ),

            'messages' => array(
                'connectionOk' => __( 'Connection parameters are valid.', 'ari-adminer' ),

                'connectionFailed' => __( 'Could not connect to DB. The following error occurs: ', 'ari-adminer' ),
            ),
        );

        return $app_options;
    }
}
