<?php
namespace Ari\Controls\Paging;

class Paging {
    protected $options = null;

    protected $page_count = 0;

    function __construct( $options ) {
        $this->options = new Paging_Options( $options );

        $count = $this->options->count;
        $page_size = $this->options->page_size;

        $this->page_count = $count > 0 ? ( $page_size > 0 ? ceil( $count / $page_size ) : 1 ) : 0;
        if ( $this->options->visible_page_count > $this->page_count )
            $this->options->visible_page_count = $this->page_count;
    }

    public function render() {
        require dirname( __FILE__ ) . '/tmpl/paging.php';
    }

    public function get_page_count() {
        return $this->page_count;
    }

    public function get_page_buttons() {
        $visible_page_count = $this->options->visible_page_count;
        $page_num = $this->options->page_num;
        $page_count = $this->get_page_count();

        $page_buttons = array();

        $buttons_before = min( floor( ( $visible_page_count - 1 ) / 2 ), $page_num );
        for ( $i = $buttons_before; $i > 0; $i-- )
            $page_buttons[] = $page_num - $i;

        $page_buttons[] = $page_num;
        $buttons_after = min( $visible_page_count - 1 - $buttons_before, $page_count - $page_num - 1 );
        for ( $i = 0; $i < $buttons_after; $i++ )
            $page_buttons[] = $page_num + $i + 1;

        if ( count( $page_buttons ) < $visible_page_count ) {
            $cnt = $visible_page_count - count( $page_buttons );
            for ( $i = 1; $i <= $cnt && $page_buttons[0] > 0; $i++ )
                array_unshift( $page_buttons, $page_buttons[0] - 1 );
        }

        return $page_buttons;
    }
}
