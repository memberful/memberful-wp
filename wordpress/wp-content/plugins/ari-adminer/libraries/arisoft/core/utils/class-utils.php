<?php
namespace Ari\Utils;

define( 'ARI_UTILS_OPENSSL_RPB_SUPPORTED', function_exists( 'openssl_random_pseudo_bytes' ) );

class Utils {
    public static function guid() {
        if ( ARI_UTILS_OPENSSL_RPB_SUPPORTED ) {
            $data = openssl_random_pseudo_bytes( 16 );
            $data[6] = chr( ord( $data[6] ) & 0x0f | 0x40 );
            $data[8] = chr( ord( $data[8] ) & 0x3f | 0x80 );

            return vsprintf( '%s%s-%s-%s-%s-%s%s%s', str_split( bin2hex( $data ), 4 ) );
        }

        return sprintf(
            '%04X%04X-%04X-%04X-%04X-%04X%04X%04X',
            mt_rand( 0, 65535 ),
            mt_rand( 0, 65535 ),
            mt_rand( 0, 65535 ),
            mt_rand( 16384, 20479 ),
            mt_rand( 32768, 49151 ),
            mt_rand( 0, 65535 ),
            mt_rand( 0, 65535 ),
            mt_rand( 0, 65535 )
        );
    }

    public static function get_value( $src, $key, $default = null ) {
        $val = $default;

        if ( is_array( $src ) ) {
            if ( isset( $src[$key] ) ) {
                $val = $src[$key];
            }
        } else if ( is_object( $src ) ) {
            if ( isset( $src->$key ) ) {
                $val = $src->$key;
            }
        }

        return $val;
    }
}
