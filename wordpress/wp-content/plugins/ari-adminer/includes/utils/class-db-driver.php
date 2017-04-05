<?php
namespace Ari_Adminer\Utils;

use Ari\Utils\Enum as Enum;

class Db_Driver extends Enum {
    const SERVER = 'server';

    const MYSQL = 'server';

    const SQLITE = 'sqlite';

    const POSTGRESQL = 'pgsql';
}
