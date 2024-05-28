<?php

namespace mindplay\sql\mysql;

use mindplay\sql\exceptions\ForeignKeyException;
use mindplay\sql\exceptions\SQLException;
use mindplay\sql\exceptions\UniqueConstraintException;
use mindplay\sql\framework\pdo\PDOConnection;

class MySQLConnection extends PDOConnection
{
    public function getExceptionType(string $sql_state, int $error_code, string $error_message): string
    {
        switch ($error_code) {
            case '1216':
            case '1217':
            case '1451':
            case '1452':
            case '1701':
                return ForeignKeyException::class;

            case '1062':
            case '1557':
            case '1569':
            case '1586':
                return UniqueConstraintException::class;

            default:
                return SQLException::class;
        }
    }
}
