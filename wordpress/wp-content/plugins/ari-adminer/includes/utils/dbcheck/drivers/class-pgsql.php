<?php
namespace Ari_Adminer\Utils\Dbcheck\Drivers;

use Ari_Adminer\Utils\Dbcheck\Driver as Driver;

define( 'ARI_DBCHECKED_POSTGRESQL_CONNECTION_TIMEOUT', 10 );

class Pgsql extends Driver {
    protected $error = '';

    protected function check_connection_impl() {
        $result = false;
        if ( extension_loaded( 'pgsql' ) ) {
            $connection_string = 'host=\'' . str_replace( ':', '\' port=\'', $this->q_connection_param( $this->options->host ) ) . '\' user=\'' . $this->q_connection_param( $this->options->user ) . '\' password=\'' . $this->q_connection_param( $this->options->pass ) . '\'' . ' dbname=\'' . ( ! empty( $this->options->db_name ) ? $this->q_connection_param( $this->options->db_name ) : 'postgres' ) . '\' connect_timeout=' . ARI_DBCHECKED_POSTGRESQL_CONNECTION_TIMEOUT;

            $this->set_error_handler();

            $conn = @pg_connect($connection_string, PGSQL_CONNECT_FORCE_NEW);

            restore_error_handler();

            if ( ! $conn ) {
                $this->set_error(
                    $this->error
                );
            } else {
                $result = true;
            }

            if ( $conn ) {
                pg_close( $conn );
            }
        } else if ( extension_loaded( 'pdo_pgsql' ) ) {
            $dsn = 'pgsql:host=\'' . str_replace( ':', '\' port=\'', $this->q_connection_param( $this->options->host ) ) . '\' options=\'-c client_encoding=utf8\''. ' dbname=\'' . ( ! empty( $this->options->db_name ) ? $this->q_connection_param( $this->options->db_name ) : 'postgres' ) . '\'';

            $conn = null;
            try {
                $conn = new \PDO(
                    $dsn,
                    $this->options->user,
                    $this->options->pass,
                    array(
                        \PDO::ATTR_TIMEOUT => ARI_DBCHECKED_POSTGRESQL_CONNECTION_TIMEOUT,

                        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_SILENT,
                    )
                );
            } catch ( \Exception $e ) {
                $conn = null;
                $this->set_error(
                    $e->getMessage()
                );
            }

            if ( $conn ) {
                $result = true;
            }
        }

        return $result;
    }

    protected function q_connection_param( $param ) {
        return addcslashes( $param, "'\\" );
    }

    protected function set_error_handler() {
        $this->error = '';

        set_error_handler( array($this, 'error_handler' ) );
    }

    protected function error_handler( $errno, $error ) {
        $error = html_entity_decode( strip_tags( $error ) );

        $error = preg_replace('~^[^:]*: ~', '', $error);
        $this->error = $error;
    }
}
