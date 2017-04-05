<?php
namespace Ari\Utils;

class Date {
    static public function db_gmt_to_local( $db_date, $format = null ) {
        if ( empty( $format ) )
            $format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );

        return date_i18n( $format, get_date_from_gmt( $db_date, 'U' ) );
    }

    static public function format_duration( $duration_in_seconds, $format_rules ) {
        $formatted = array();

        foreach ( $format_rules as $unit_measure => $unit_label ) {
            if ( $duration_in_seconds < $unit_measure )
                continue ;

            $mod = $duration_in_seconds % $unit_measure;
            $units = (integer)( ( $duration_in_seconds - $mod ) / $unit_measure );

            $formatted[] = $units . ' ' . $unit_label;

            $duration_in_seconds = $mod;

            if ( 0 === $duration_in_seconds )
                break;
        }

        return join( ' ', $formatted );
    }
}