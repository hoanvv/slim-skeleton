<?php

namespace Hoanvv\App\Database;

use \PDO;
use \PDOException;
class MasterDatabase
{
    // create DB connection
    public function db()
    {
        $servername = "db"; // name of container
        $username = "app";
        $password = "app";

        try {
            $conn = new PDO("mysql:host=$servername;dbname=slim", $username, $password);
            // set the PDO error mode to exception
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $conn;
        } catch (PDOException $e) {
            throw new \PDOException($e->getMessage(), $e->getCode());
        }
    }
}
