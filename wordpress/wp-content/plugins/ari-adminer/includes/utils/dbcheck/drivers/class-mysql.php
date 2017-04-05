<?php
namespace Ari_Adminer\Utils\Dbcheck\Drivers;

use Ari_Adminer\Utils\Dbcheck\Driver as Driver;

define( 'ARI_DBCHECKED_MYSQL_CONNECTION_TIMEOUT', 10 );

class Mysql extends Driver {
    protected function check_connection_impl() {
        $db_server = $this->options->host;
        $db_host = $this->options->host;
        $db_port = null;

        if ( strpos( $db_server, ':' ) !== false ) {
            list( $db_host, $db_port) = explode( ':', $db_server );
        }
        $db_name = $this->options->db_name;
        $db_user = $this->options->user;
        $db_pass = $this->options->pass;

        $result = false;
        if ( extension_loaded( 'mysqli' ) ) {
            if ( empty( $db_host ) ) {
                $db_host = ini_get( 'mysqli.default_host' );

                if ( empty( $db_user ) ) {
                    $db_user = ini_get( 'mysqli.default_user' );

                    if ( empty( $db_pass ) ) {
                        $db_pass = ini_get( 'mysqli.default_pw' );

                        if ( ! is_numeric( $db_port ) )
                             $db_port = ini_get( 'mysqli.default_port' );
                    }
                }
            }

            $conn = mysqli_init();
            if ( ! $conn ) {
                $this->set_error(
                    'mysqli_init() failed'
                );
            }
            else {
                $conn->options( MYSQLI_OPT_CONNECT_TIMEOUT, ARI_DBCHECKED_MYSQL_CONNECTION_TIMEOUT );

                if ( ! $conn->real_connect(
                    $db_host,
                    $db_user,
                    $db_pass,
                    $db_name,
                    $db_port
                    ) || ! $conn->select_db( $db_name )
                ) {
                    $this->set_error(
                        sprintf(
                            '%s - %s',
                            mysqli_connect_errno(),
                            mysqli_connect_error()
                        )
                    );
                } else {
                    $result = true;
                }
            }

            if ( $conn )
                $conn->close();
        } else if ( extension_loaded( 'mysql' ) && ! ( ini_get( 'sql.safe_mode' ) && extension_loaded( 'pdo_mysql' ) ) ) {
            if ( empty( $db_server ) ) {
                $db_server = ini_get( 'mysql.default_host' );

                if ( empty( $db_user ) ) {
                    $db_user = ini_get( 'mysql.default_user' );

                    if ( empty( $db_pass ) ) {
                        $db_pass = ini_get( 'mysql.default_password' );
                    }
                }
            }

            $conn = mysql_connect(
                $db_server,
                $db_user,
                $db_pass,
                true,
                131072
            );

            if ( empty( $conn ) ) {
                $this->set_error(
                    mysql_error()
                );
            } else {
                if ( ! mysql_select_db( $db_name, $conn ) ) {
                    $this->set_error(
                        mysql_error()
                    );
                } else {
                    $result = true;
                }
            }

            mysql_close( $conn );
        } else if ( extension_loaded( 'pdo_mysql' ) ) {
            $db_name = '`' . str_replace( '`', '``', $db_name ) . '`';
            $dsn = 'mysql:charset=utf8;host=' . str_replace( ':', ';unix_socket=', preg_replace( '~:(\\d)~', ';port=\\1', $db_server ) );

            $conn = null;
            try {
                $conn = new \PDO(
                    $dsn,
                    $db_user,
                    $db_pass,
                    array(
                        \PDO::ATTR_TIMEOUT => ARI_DBCHECKED_MYSQL_CONNECTION_TIMEOUT,

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
                if ( ! $conn->query( 'USE ' . $db_name ) ) {
                    $error = $conn->errorInfo();

                    if ( $error ) {
                        $this->set_error(
                            sprintf(
                                '%s - %s - %s',
                                $error[0],
                                $error[1],
                                $error[2]
                            )
                        );
                    }
                } else {
                    $result = true;
                }
            }
        } else {
            $this->set_error(
                'Install MySQLi, MySQL or PDO MySQL PHP extension to connect to MySQL databases.'
            );
        }

        return $result;
    }
}
