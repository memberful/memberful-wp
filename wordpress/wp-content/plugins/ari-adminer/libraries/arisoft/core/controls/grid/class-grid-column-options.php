<?php
namespace Ari\Controls\Grid;

use Ari\Utils\Options as Options;

class Grid_Column_Options extends Options {
    public $key = '';

    public $title = null;

    public $header = '';

    public $column = null;

    public $header_class = '';

    public $class = '';

    public $sortable = false;

    public $virtual = false;

    public $header_tag = 'th';

    public $tag = 'td';

    function __construct( $options = array() ) {
        parent::__construct( $options );

        if ( is_null( $this->title ) && is_string( $this->header ) )
            $this->title = $this->header;
    }
}
