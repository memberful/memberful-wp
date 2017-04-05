<?php
namespace Ari_Adminer\Controls\Grid;

use Ari\Controls\Grid\Grid as Grid_Base;
use Ari\Utils\Array_Helper as Array_Helper;

class Grid extends Grid_Base {
    function __construct( $id, $options ) {
        $main_options = Array_Helper::get_value( $options, 'options', array() );

        $main_options = array_replace(
            array(
                'no_data_message' => __( 'No results found', 'ari-adminer' ),

                'class' => 'wp-list-table widefat fixed striped ',

                'header_class' => 'grey lighten-5',
            ),
            $main_options
        );

        $options['options'] = $main_options;

        parent::__construct( $id, $options );
    }
}
