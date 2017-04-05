<?php
namespace Ari_Adminer\Views\Settings;

use Ari_Adminer\Views\Base as Base;

class Html extends Base {
    public function display( $tmpl = null ) {
        $this->set_title( __( 'ARI Adminer - Settings', 'ari-adminer' ) );

        wp_enqueue_script( 'jquery-ui-tooltip' );
        wp_enqueue_script( 'ari-adminer-page-settings', ARIADMINER_ASSETS_URL . 'common/pages/settings.js', array( 'ari-adminer-app' ), ARIADMINER_VERSION );

        parent::display( $tmpl );
    }
}
