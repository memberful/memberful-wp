<?php
namespace Ari\Database;

class Helper {
    public static function split_sql( $sql, $prepare_query = true ) {
        $start = 0;
        $open = false;
        $comment = false;
        $endString = '';
        $end = strlen( $sql );
        $queries = array();
        $query = '';

        for ( $i = 0; $i < $end; $i++ ) {
            $current = substr( $sql, $i, 1 );
            $current2 = substr( $sql, $i, 2 );
            $current3 = substr( $sql, $i, 3 );
            $lenEndString = strlen( $endString );
            $testEnd = substr( $sql, $i, $lenEndString );

            if ( $current == '"' || $current == "'" || $current2 == '--'
                || ( $current2 == '/*' && $current3 != '/*!' && $current3 != '/*+' )
                || ( $current == '#' && $current3 != '#__' )
                || ( $comment && $testEnd == $endString ) ) {

                $n = 2;

                while ( substr( $sql, $i - $n + 1, 1 ) == '\\' && $n < $i ) {
                    $n++;
                }

                if ( $n % 2 == 0 ) {
                    if ( $open ) {
                        if ( $testEnd == $endString ) {
                            if ( $comment ) {
                                $comment = false;
                                if ( $lenEndString > 1 ) {
                                    $i += ( $lenEndString - 1 );
                                    $current = substr( $sql, $i, 1 );
                                }
                                $start = $i + 1;
                            }
                            $open = false;
                            $endString = '';
                        }
                    } else {
                        $open = true;
                        if ( $current2 == '--' ) {
                            $endString = "\n";
                            $comment = true;
                        } elseif ( $current2 == '/*' ) {
                            $endString = '*/';
                            $comment = true;
                        } elseif ( $current == '#' ) {
                            $endString = "\n";
                            $comment = true;
                        } else {
                            $endString = $current;
                        }

                        if ($comment && $start < $i) {
                            $query = $query . substr( $sql, $start, ( $i - $start ) );
                        }
                    }
                }
            }

            if ($comment) {
                $start = $i + 1;
            }

            if ( ( $current == ';' && ! $open ) || $i == $end - 1 ) {
                if ( $start <= $i ) {
                    $query = $query . substr( $sql, $start, ( $i - $start + 1 ) );
                }
                $query = trim( $query );

                if ( $query ) {
                    if ( ( $i == $end - 1 ) && ( $current != ';' ) ) {
                        $query = $query . ';';
                    }

                    if ( $prepare_query )
                        $query = self::prepare_query( $query );

                    $queries[] = $query;
                }

                $query = '';
                $start = $i + 1;
            }
        }

        return $queries;
    }

    public static function prepare_query( $query ) {
        global $wpdb;

        $query = str_replace(
            '#__',

            $wpdb->prefix,

            $query
        );

        return $query;
    }

    public static function column_exists( $table, $column ) {
        global $wpdb;

        $query = sprintf(
            'SHOW COLUMNS FROM %s LIKE "%s"',
            $table,
            $column
        );
        $query = self::prepare_query( $query );

        $columns = $wpdb->get_row( $query, ARRAY_N );

        return ( ! empty( $columns ) && count( $columns) > 0 );
    }

    public static function index_exists( $table, $index ) {
        global $wpdb;

        $query = 'SHOW INDEX FROM ' . $table;
        $query = self::prepare_query( $query );

        $keys = $wpdb->get_results( $query, ARRAY_A );
        if ( is_array( $keys ) ) {
            foreach ( $keys as $key_info ) {
                if ( isset( $key_info['Key_name'] ) && $key_info['Key_name'] == $index ) {
                    return true;
                }
            }
        }

        return false;
    }

    public static function is_utf8mb4_supported() {
        global $wpdb;

        $res = $wpdb->get_var( 'SELECT COUNT(*) FROM information_schema.character_sets WHERE `CHARACTER_SET_NAME` = "utf8mb4"' );

        return !! $res;
    }
}
