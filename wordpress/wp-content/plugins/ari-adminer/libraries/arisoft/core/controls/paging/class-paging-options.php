<?php
namespace Ari\Controls\Paging;

use Ari\Utils\Options as Options;

class Paging_Options extends Options {
    public $visible_page_count = 3;

    public $page_num = 0;

    public $page_size = 0;

    public $count = 0;

    public $go_to_message = '';
}
