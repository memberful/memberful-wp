<?php
namespace Ari\Controls\Grid;

class Grid {
    protected $id = null;

    protected $options = null;

    function __construct( $id, $options ) {
        $this->id = $id;
        $this->options = new Grid_Options( $options );
    }

    public function render( $data ) {
        require dirname( __FILE__ ) . '/tmpl/grid.php';
    }
}
