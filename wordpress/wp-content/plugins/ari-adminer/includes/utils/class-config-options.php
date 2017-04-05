<?php
namespace Ari_Adminer\Utils;

use Ari\Utils\Options as Options;

class Config_Options extends Options {
    public $title = 'Adminer';

    public $theme_url;

    public $db_driver = 'server'; //server, sqlite, pgsql, oracle, mssql, firebird, simpledb, mongo, elastic

    public $db_host = 'localhost';

    public $db_name;

    public $db_user;

    public $db_pass;
}
