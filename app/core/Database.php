<?php
namespace App\Core;

use PDO;

class Database {
    private $connection;

    public function __construct(array $config) {
        error_log("Connecting to database with config: " . json_encode($config));
        try {
            $this->connection = new PDO(
                "{$config['driver']}:host={$config['host']};dbname={$config['database']};port={$config['port']}",
                $config['username'],
                $config['password']
            );

            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (\PDOException $e) {
            error_log("Database connection error: " . $e->getMessage());
            throw new \Exception("Database connection not established: " . $e->getMessage());
        }
    }

    public function getConnection() {
        return $this->connection;
    }
}
