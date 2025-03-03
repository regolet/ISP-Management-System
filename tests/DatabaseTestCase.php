<?php
namespace Tests;

use App\Core\Database;

abstract class DatabaseTestCase extends TestCase
{
    protected ?Database $db = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = $this->app->getDB();
        $this->beginTransaction();
    }

    protected function tearDown(): void
    {
        $this->rollback();
        $this->db = null;
        parent::tearDown();
    }

    protected function beginTransaction(): void
    {
        if ($this->db) {
            $this->db->beginTransaction();
        }
    }

    protected function rollback(): void
    {
        if ($this->db) {
            $this->db->rollback();
        }
    }

    protected function assertDatabaseHas(string $table, array $criteria): void
    {
        $query = "SELECT COUNT(*) as count FROM {$table} WHERE ";
        $conditions = [];
        $params = [];

        foreach ($criteria as $column => $value) {
            $conditions[] = "{$column} = ?";
            $params[] = $value;
        }

        $query .= implode(' AND ', $conditions);
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        $this->assertGreaterThan(
            0, 
            $result['count'], 
            "Failed asserting that table '{$table}' contains the expected record."
        );
    }

    protected function assertDatabaseMissing(string $table, array $criteria): void
    {
        $query = "SELECT COUNT(*) as count FROM {$table} WHERE ";
        $conditions = [];
        $params = [];

        foreach ($criteria as $column => $value) {
            $conditions[] = "{$column} = ?";
            $params[] = $value;
        }

        $query .= implode(' AND ', $conditions);
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        $this->assertEquals(
            0, 
            $result['count'], 
            "Failed asserting that table '{$table}' does not contain the expected record."
        );
    }

    protected function assertGreaterThan($expected, $actual, string $message = ''): void
    {
        parent::assertGreaterThan($expected, $actual, $message);
    }

    protected function assertEquals($expected, $actual, string $message = ''): void
    {
        parent::assertEquals($expected, $actual, $message);
    }
}
