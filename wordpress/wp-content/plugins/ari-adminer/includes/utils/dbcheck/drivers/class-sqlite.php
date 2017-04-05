<?php
namespace Ari_Adminer\Utils\Dbcheck\Drivers;

use Ari_Adminer\Utils\Dbcheck\Driver as Driver;

class Sqlite extends Driver {
    protected function check_connection_impl() {
        $result = false;
        $db = $this->options->db_name;

        if ( empty( $db ) ) {
            $this->set_error( 'Path to DB file is not specified' );
        } else if ( class_exists( 'SQLite3' ) ) {
            $conn = null;

            try {
                $conn = new \SQLite3( $db, SQLITE3_OPEN_READWRITE );
            } catch ( \Exception $e ) {
                $conn = null;
                $this->set_error(
                    $e->getMessage()
                );
            }

            if ( $conn ) {
                $result = true;
            }
        } else if ( extension_loaded( 'pdo_sqlite' ) ) {
            $dsn = 'sqlite:' . $db;

            $conn = null;
            try {
                $conn = new \PDO(
                    $dsn
                );
            } catch ( \Exception $e ) {
                $conn = null;
                $this->set_error(
                    $e->getMessage()
                );
            }

            if ( $conn )
                $result = true;
        } else {
            $this->set_error(
                'SQLite3 or PDO SQLite PHP extension is not installed'
            );
        }

        return $result;
    }
}
