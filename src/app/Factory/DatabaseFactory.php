<?php

namespace Hoanvv\App\Factory;

use PDO;
use PDOException;
use PDOStatement;

class DatabaseFactory
{
    public const MYSQL_TYPE = 'mysql';
    public const MSSQL_TYPE = 'sqlsrv';
    public const PGSQL_TYPE = 'pgsql';
    public const MYSQL_PORT = '3306';
    public const MSSQL_PORT = '1433';
    public const PGSQL_PORT = '5432';

    private array $vars;

    private PDO $connection;

    private PDOStatement $statement;

    public function __construct()
    {
        $this->getEnvVariables();

        $pdo = new PDO($this->getDsn(), $this->vars['user'], $this->vars['password']);
        $this->setPDOAttributes($pdo);

        $this->connection = $pdo;
    }

    public function getConnection(): PDO
    {
        return $this->connection;
    }

    /**
     * Set SQL query
     *
     * @throws PDOException
     */
    public function prepare(string $sql, array &$data = [], array $options = []): PDOStatement
    {
        // Prepare query statement
        $stmt = $this->connection->prepare($sql, $options);

        foreach ($data as $key => &$value) {
            $stmt->bindParam($key, $value);
        }

        $this->statement = $stmt;

        return $stmt;
    }

    /**
     * Run the SQL query
     *
     * @throws PDOException
     */
    public function execute(string $sql, array $data = []): bool
    {
        $this->prepare($sql, $data);

        return $this->statement->execute();
    }

    /**
     * Run the SQL query and
     * get the first result
     * @throws PDOException
     * @return mixed
     */
    public function first(string $sql, array $data = [])
    {
        $this->prepare($sql, $data);
        // Execute query
        $this->statement->execute();
        $total = $this->statement->rowCount();
        // Check if more than 0 record found
        $result = [];
        if ($total > 0) {
            while ($row = $this->statement->fetch(PDO::FETCH_ASSOC)) {
                $result[] = (object)$row;
            }

            return $result[0];
        } else {
            return null;
        }
    }

    /**
     * Run the SQL query and
     * get all the result
     *
     * @throws PDOException
     */
    public function get(string $sql, array $data = []): array
    {
        $this->prepare($sql, $data);
        // Execute query
        $this->statement->execute();
        $total = $this->statement->rowCount();
        // Check if more than 0 record found
        $result = [];
        if ($total > 0) {
            while ($row = $this->statement->fetch(PDO::FETCH_ASSOC)) {
                $result[] = (object)$row;
            }
        }

        return $result;
    }

    /**
     * get the latest inserted id
     *
     * @throws PDOException
     */
    public function insertedId(): int
    {
        return (int) $this->connection->lastInsertId();
    }

    public function beginTransaction(): bool
    {
        if ($this->connection->inTransaction()) {
            return true;
        }

        return $this->connection->beginTransaction();
    }

    public function rollback(): bool
    {
        if ($this->connection->inTransaction()) {
            return $this->connection->rollback();
        }

        return true;
    }

    public function commit(): bool
    {
        if ($this->connection->inTransaction()) {
            return $this->connection->commit();
        }

        return true;
    }

    private function getDsn(): string
    {
        $type = $this->vars['type'];
        $host = $this->vars['host'];
        $database = $this->vars['database'];
        $port = $this->vars['port'];

        switch ($type) {
            case self::MSSQL_TYPE:
                return "$type:Server=$host,$port;Database=$database;Encrypt=no";

            default:
                return "$type:host=$host;port=$port;dbname=$database";
        }
    }

    private function setPDOAttributes(PDO $pdo): void
    {
        $pdo->setAttribute(\PDO::ATTR_STRINGIFY_FETCHES, false);
        $pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        if (self::MSSQL_TYPE === $this->vars['type']) {
            $pdo->setAttribute(\PDO::SQLSRV_ATTR_FORMAT_DECIMALS, true);
        }
    }

    private function getEnvVariables(): void
    {
        $type = $_ENV['DB_TYPE'] ?? self::MYSQL_TYPE;

        $variables = [
            self::MYSQL_TYPE => [
                'type' => $type,
                'port' => $_ENV['DB_PORT'] ?? self::MYSQL_PORT,
                'host' => $_ENV['DB_HOST'] ?? '',
                'database' => $_ENV['DB_NAME'] ?? '',
                'user' => $_ENV['DB_USER'] ?? '',
                'password' => $_ENV['DB_PASSWORD'] ?? '',
            ],
            self::MSSQL_TYPE => [
                'type' => $type,
                'port' => $_ENV['DB_PORT'] ?? self::MSSQL_PORT,
                'host' => $_ENV['DB_HOST'] ?? '',
                'database' => $_ENV['DB_NAME'] ?? '',
                'user' => $_ENV['DB_USER'] ?? '',
                'password' => $_ENV['DB_PASSWORD'] ?? '',
            ],
            self::PGSQL_TYPE => [
                'type' => $type,
                'port' => $_ENV['DB_PORT'] ?? self::PGSQL_PORT,
                'host' => $_ENV['DB_HOST'] ?? '',
                'database' => $_ENV['DB_NAME'] ?? '',
                'user' => $_ENV['DB_USER'] ?? '',
                'password' => $_ENV['DB_PASSWORD'] ?? '',
            ],
        ];

        $this->vars = $variables[$type];
    }
}
