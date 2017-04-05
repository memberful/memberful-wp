<?php
namespace Ari\Utils;

class Response {
    public static function redirect( $url, $status = 302 ) {
        wp_redirect( $url, $status );

        exit();
    }
}