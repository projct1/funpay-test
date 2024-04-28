<?php

namespace FpDbTest;

use Exception;
use FpDbTest\Database\Database;
use FpDbTest\Database\DatabaseTest;
use mysqli;

const DB_HOST = 'localhost';
const DB_USER = 'root';
const DB_PASS = 12345;
const DB_NAME = 'test';
const DB_PORT = 3306;

spl_autoload_register(function ($class) {
    require str_replace(
        '\\', DIRECTORY_SEPARATOR, substr($class, strpos($class, '\\') + 1)
    ) . '.php';
});

try {
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

    if ($mysqli->connect_errno) {
        throw new Exception($mysqli->connect_error);
    }

    $db = new Database($mysqli);
    $test = new DatabaseTest($db);

    echo $test->testBuildQuery() ? 'OK' : 'FAILURE';
} catch (Exception $e) {
    echo $e->getMessage();
}
