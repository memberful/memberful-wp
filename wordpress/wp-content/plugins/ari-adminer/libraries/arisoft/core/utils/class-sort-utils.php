<?php
namespace Ari\Utils;

class Sort_Utils {
    private $key;
    private $dir;
    private $cmp;

    function __construct( $key, $dir = 'asc', $cmp = 'string' ) {
        $this->key = $key;
        $this->dir = strtolower($dir);
        $this->cmp = strtolower($cmp);
    }

    function sort( $a, $b ) {
        $key = $this->key;
        $aVal = is_array( $a ) ? $a[$key] : $a->$key;
        $bVal = is_array( $b ) ? $b[$key] : $b->$key;

        $res = 0;
        if ($this->cmp == 'natural')
            $res = strnatcmp( $aVal, $bVal );
        else
            $res = strcmp( $aVal, $bVal );

        return $this->dir == 'asc' ? $res : -$res;
    }
}
