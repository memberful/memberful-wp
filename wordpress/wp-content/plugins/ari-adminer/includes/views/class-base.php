<?php
namespace Ari_Adminer\Views;

use Ari\Views\View as View;
use Ari\Utils\Request as Request;

class Base extends View {
    protected $title = '';

    public function display( $tmpl = null ) {
        wp_enqueue_style( 'ari-adminer' );
        wp_enqueue_script( 'ari-adminer-app' );
        wp_enqueue_script( 'ari-adminer-app-helper' );

        echo '<div id="ari_adminer_plugin" class="wrap">';

        $this->render_message();
        $this->render_title();

        parent::display( $tmpl );

        echo '</div>';
        $app_options = $this->get_app_options();

        $app_helper_options = array(
            'messages' => array(
                'yes' => __( 'Yes', 'ari-adminer' ),

                'no' => __( 'No', 'ari-adminer' ),

                'ok' => __( 'OK', 'ari-adminer' ),

                'cancel' => __( 'Cancel', 'ari-adminer' ),

                'close' => __( 'Close', 'ari-adminer' ),
            )
        );

        $global_app_options = array(
            'options' => $app_helper_options,

            'app' => $app_options,
        );
        printf(
            '<script>window["ARI_APP"] = %1$s;</script>',
            json_encode( $global_app_options, JSON_NUMERIC_CHECK )
        );
    }

    public function set_title( $title ) {
        $this->title = $title;
    }

    protected function render_title() {
        if ( $this->title )
            printf(
                '<h1 class="wp-heading-inline">%s</h1>',
                $this->title
            );
    }

    protected function render_message() {
        if ( ! Request::exists( 'msg' ) )
            return ;

        $message_type = Request::get_var( 'msg_type', ARIADMINER_MESSAGETYPE_NOTICE, 'alpha' );
        $message = Request::get_var( 'msg' );

        printf(
            '<div class="notice notice-%2$s is-dismissible"><p>%1$s</p></div>',
            $message,
            $message_type
        );
    }

    protected function get_app_options() {
        return null;
    }
}
