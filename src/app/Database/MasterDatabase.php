<?php

namespace Hoanvv\App\Database;

use \PDO;
use \PDOException;

class MasterDatabase implements IMasterDatabase
{
    // create DB connection
    public $conn;
    public function __construct()
    {
        $servername = "db"; // name of container
        $username = "app";
        $password = "app";

        try {
            $this->conn = new PDO("mysql:host=$servername;dbname=slim", $username, $password);
            // set the PDO error mode to exception
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            throw new \PDOException($e->getMessage(), $e->getCode());
        }
    }

    public function query($sql)
    {
        return $this->conn->query($sql);
    }

    public function prepare($sql)
    {
        return $this->conn->prepare($sql);
    }

    public function beginTransaction()
    {
        return $this->conn->beginTransaction();
    }

    public function rollback()
    {
        return $this->conn->rollback();
    }
}
