<?php
namespace Ari\Controls\Grid;

use Ari\Utils\Array_Helper as Array_Helper;

class Grid_Options {
    public $options = null;

    public $columns = array();

    function __construct( $options = array() ) {
        $grid_options = Array_Helper::get_value( $options, 'options', array() );
        $columns = Array_Helper::get_value( $options, 'columns', array() );

        $this->options = new Grid_Main_Options( $grid_options );

        foreach ( $columns as $column ) {
            $this->columns[] = new Grid_Column_Options( $column );
        }
    }
}
