<?php
namespace Ari_Adminer\Helpers;

class Screen {
    static public function register() {
        $screen = get_current_screen();

        $screen->add_help_tab(
            array(
                'id' => 'adminer_help_tab',
                'title'	=> __( 'Help', 'ari-adminer' ),
                'content' => sprintf(
                    '<p>' . __( 'User\'s guide is available <a href="%s" target="_blank">here</a>.', 'ari-adminer') . '</p>',
                    'http://ari-soft.com/docs/wordpress/ari-adminer/v1/en/index.html'
                )
            )
        );
    }
}
