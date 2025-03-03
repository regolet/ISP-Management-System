<?php
namespace App\Core\Database;

use App\Core\Cache\CacheInterface;

class QueryBuilder 
{
    private $db;
    private $cache;
    private $table;
    private $select = '*';
    private $where = [];
    private $joins = [];
    private $orderBy = [];
    private $groupBy = [];
    private $having = [];
    private $limit;
    private $offset;
    private $params = [];
    private $useCache = true;
    private $cacheTTL = 3600;
    private $eagerLoads = [];

    public function __construct(\mysqli $db, ?CacheInterface $cache = null) 
    {
        $this->db = $db;
        $this->cache = $cache;
    }

    public function table(string $table): self 
    {
        $this->table = $table;
        return $this;
    }

    public function select($columns): self 
    {
        $this->select = is_array($columns) ? implode(', ', $columns) : $columns;
        return $this;
    }

    public function where($column, $operator = null, $value = null): self 
    {
        if (is_array($column)) {
            foreach ($column as $key => $value) {
                $this->where($key, '=', $value);
            }
            return $this;
        }

        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $this->where[] = [$column, $operator, $value];
        $this->params[] = $value;
        return $this;
    }

    public function join(string $table, string $first, string $operator, string $second, string $type = 'INNER'): self 
    {
        $this->joins[] = compact('table', 'first', 'operator', 'second', 'type');
        return $this;
    }

    public function leftJoin(string $table, string $first, string $operator, string $second): self 
    {
        return $this->join($table, $first, $operator, $second, 'LEFT');
    }

    public function orderBy(string $column, string $direction = 'ASC'): self 
    {
        $this->orderBy[] = [$column, strtoupper($direction)];
        return $this;
    }

    public function groupBy($columns): self 
    {
        $this->groupBy = is_array($columns) ? $columns : func_get_args();
        return $this;
    }

    public function having($column, $operator = null, $value = null): self 
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $this->having[] = [$column, $operator, $value];
        $this->params[] = $value;
        return $this;
    }

    public function limit(int $limit): self 
    {
        $this->limit = $limit;
        return $this;
    }

    public function offset(int $offset): self 
    {
        $this->offset = $offset;
        return $this;
    }

    public function with(array $relations): self 
    {
        $this->eagerLoads = array_merge($this->eagerLoads, $relations);
        return $this;
    }

    public function useCache(bool $use = true): self 
    {
        $this->useCache = $use;
        return $this;
    }

    public function cacheTTL(int $seconds): self 
    {
        $this->cacheTTL = $seconds;
        return $this;
    }

    public function get(): array 
    {
        $sql = $this->toSql();
        $cacheKey = $this->getCacheKey($sql, $this->params);

        if ($this->useCache && $this->cache) {
            $cached = $this->cache->get($cacheKey);
            if ($cached !== null) {
                return $this->loadEagerRelations($cached);
            }
        }

        $stmt = $this->db->prepare($sql);
        if ($stmt === false) {
            throw new \Exception("Query preparation failed: " . $this->db->error);
        }

        if (!empty($this->params)) {
            $types = str_repeat('s', count($this->params));
            $stmt->bind_param($types, ...$this->params);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        if ($this->useCache && $this->cache) {
            $this->cache->set($cacheKey, $data, $this->cacheTTL);
        }

        return $this->loadEagerRelations($data);
    }

    public function first() 
    {
        $result = $this->limit(1)->get();
        return !empty($result) ? $result[0] : null;
    }

    public function count(): int 
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        $stmt->close();
        return (int) $data['count'];
    }

    private function toSql(): string 
    {
        $sql = "SELECT {$this->select} FROM {$this->table}";

        // Add joins
        foreach ($this->joins as $join) {
            $sql .= " {$join['type']} JOIN {$join['table']} ON {$join['first']} {$join['operator']} {$join['second']}";
        }

        // Add where conditions
        if (!empty($this->where)) {
            $sql .= " WHERE ";
            $conditions = [];
            foreach ($this->where as $where) {
                $conditions[] = "{$where[0]} {$where[1]} ?";
            }
            $sql .= implode(' AND ', $conditions);
        }

        // Add group by
        if (!empty($this->groupBy)) {
            $sql .= " GROUP BY " . implode(', ', $this->groupBy);
        }

        // Add having
        if (!empty($this->having)) {
            $sql .= " HAVING ";
            $conditions = [];
            foreach ($this->having as $having) {
                $conditions[] = "{$having[0]} {$having[1]} ?";
            }
            $sql .= implode(' AND ', $conditions);
        }

        // Add order by
        if (!empty($this->orderBy)) {
            $sql .= " ORDER BY ";
            $orders = [];
            foreach ($this->orderBy as $order) {
                $orders[] = "{$order[0]} {$order[1]}";
            }
            $sql .= implode(', ', $orders);
        }

        // Add limit and offset
        if ($this->limit !== null) {
            $sql .= " LIMIT {$this->limit}";
            if ($this->offset !== null) {
                $sql .= " OFFSET {$this->offset}";
            }
        }

        return $sql;
    }

    private function getCacheKey(string $sql, array $params): string 
    {
        return 'query_' . md5($sql . serialize($params));
    }

    private function loadEagerRelations(array $data): array 
    {
        if (empty($this->eagerLoads) || empty($data)) {
            return $data;
        }

        foreach ($this->eagerLoads as $relation => $constraints) {
            // Implementation of eager loading would go here
            // This would require relationship definitions in the models
        }

        return $data;
    }
}
