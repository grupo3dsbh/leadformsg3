<?php

declare(strict_types=1);

namespace Core;

/**
 * Database - Singleton PDO Wrapper
 *
 * Provides a single shared PDO connection, convenience methods for
 * prepared-statement queries, and a lightweight fluent query builder.
 *
 * Connection parameters are read from environment variables:
 *   DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS
 */
class Database
{
    private static ?Database $instance = null;
    private \PDO $pdo;

    // ------------------------------------------------------------------
    // Query builder state (reset after each execute / get)
    // ------------------------------------------------------------------

    private string $builderTable   = '';
    private array  $builderSelect  = ['*'];
    private array  $builderWhere   = [];
    private array  $builderBinds   = [];
    private array  $builderOrder   = [];
    private array  $builderJoin    = [];
    private ?int   $builderLimit   = null;
    private ?int   $builderOffset  = null;
    private ?string $builderGroup  = null;
    private ?string $builderHaving = null;

    // ------------------------------------------------------------------
    // Connection
    // ------------------------------------------------------------------

    private function __construct()
    {
        $host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: '127.0.0.1';
        $port = $_ENV['DB_PORT'] ?? getenv('DB_PORT') ?: '3306';
        $name = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'leadform';
        $user = $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'root';
        $pass = $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?: '';

        $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";

        $this->pdo = new \PDO($dsn, $user, $pass, [
            \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES   => false,
            \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        ]);
    }

    /** Prevent cloning. */
    private function __clone(): void {}

    /**
     * Get the singleton instance.
     */
    public static function getInstance(): static
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * Direct access to the underlying PDO object.
     */
    public function getPdo(): \PDO
    {
        return $this->pdo;
    }

    // ------------------------------------------------------------------
    // Raw query helpers
    // ------------------------------------------------------------------

    /**
     * Execute a raw SQL query with optional bindings.
     */
    public function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt;
    }

    /**
     * Fetch all rows from a raw query.
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    /**
     * Fetch a single row.
     */
    public function fetch(string $sql, array $params = []): array|false
    {
        return $this->query($sql, $params)->fetch();
    }

    /**
     * Fetch a single scalar value (first column of first row).
     */
    public function fetchColumn(string $sql, array $params = []): mixed
    {
        return $this->query($sql, $params)->fetchColumn();
    }

    /**
     * Execute an INSERT / UPDATE / DELETE and return affected row count.
     */
    public function execute(string $sql, array $params = []): int
    {
        return $this->query($sql, $params)->rowCount();
    }

    /**
     * Return the last inserted auto-increment ID.
     */
    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }

    // ------------------------------------------------------------------
    // Transaction helpers
    // ------------------------------------------------------------------

    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    public function rollBack(): bool
    {
        return $this->pdo->rollBack();
    }

    /**
     * Run a closure inside a transaction; auto-commit on success, rollback on
     * exception.
     */
    public function transaction(\Closure $callback): mixed
    {
        $this->beginTransaction();

        try {
            $result = $callback($this);
            $this->commit();

            return $result;
        } catch (\Throwable $e) {
            $this->rollBack();
            throw $e;
        }
    }

    // ------------------------------------------------------------------
    // Fluent query builder
    // ------------------------------------------------------------------

    /**
     * Start a new query builder chain for the given table.
     */
    public function table(string $table): static
    {
        $this->resetBuilder();
        $this->builderTable = $table;

        return $this;
    }

    /**
     * Set the columns to select.
     */
    public function select(string ...$columns): static
    {
        $this->builderSelect = $columns ?: ['*'];
        return $this;
    }

    /**
     * Add a WHERE clause (AND).
     */
    public function where(string $column, mixed $operatorOrValue, mixed $value = null): static
    {
        if ($value === null) {
            $value    = $operatorOrValue;
            $operator = '=';
        } else {
            $operator = $operatorOrValue;
        }

        $placeholder = ':w_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $column) . '_' . count($this->builderWhere);
        $this->builderWhere[] = "{$column} {$operator} {$placeholder}";
        $this->builderBinds[$placeholder] = $value;

        return $this;
    }

    /**
     * Add a WHERE IN clause.
     */
    public function whereIn(string $column, array $values): static
    {
        if (empty($values)) {
            // Impossible condition.
            $this->builderWhere[] = '1 = 0';
            return $this;
        }

        $placeholders = [];
        foreach ($values as $i => $val) {
            $key = ':win_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $column) . "_{$i}";
            $placeholders[]             = $key;
            $this->builderBinds[$key]   = $val;
        }

        $this->builderWhere[] = "{$column} IN (" . implode(', ', $placeholders) . ')';

        return $this;
    }

    /**
     * Add a raw WHERE clause.
     */
    public function whereRaw(string $expression, array $binds = []): static
    {
        $this->builderWhere[]  = $expression;
        $this->builderBinds    = array_merge($this->builderBinds, $binds);

        return $this;
    }

    /**
     * Add a WHERE IS NULL clause.
     */
    public function whereNull(string $column): static
    {
        $this->builderWhere[] = "{$column} IS NULL";
        return $this;
    }

    /**
     * Add a WHERE IS NOT NULL clause.
     */
    public function whereNotNull(string $column): static
    {
        $this->builderWhere[] = "{$column} IS NOT NULL";
        return $this;
    }

    /**
     * Add a JOIN clause.
     */
    public function join(string $table, string $first, string $operator, string $second, string $type = 'INNER'): static
    {
        $this->builderJoin[] = "{$type} JOIN {$table} ON {$first} {$operator} {$second}";
        return $this;
    }

    public function leftJoin(string $table, string $first, string $operator, string $second): static
    {
        return $this->join($table, $first, $operator, $second, 'LEFT');
    }

    /**
     * Add ORDER BY.
     */
    public function orderBy(string $column, string $direction = 'ASC'): static
    {
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $this->builderOrder[] = "{$column} {$direction}";

        return $this;
    }

    /**
     * Add GROUP BY.
     */
    public function groupBy(string $column): static
    {
        $this->builderGroup = $column;
        return $this;
    }

    /**
     * Add HAVING.
     */
    public function having(string $expression): static
    {
        $this->builderHaving = $expression;
        return $this;
    }

    /**
     * Set a LIMIT.
     */
    public function limit(int $limit): static
    {
        $this->builderLimit = $limit;
        return $this;
    }

    /**
     * Set an OFFSET.
     */
    public function offset(int $offset): static
    {
        $this->builderOffset = $offset;
        return $this;
    }

    // ------------------------------------------------------------------
    // Builder terminals
    // ------------------------------------------------------------------

    /**
     * Execute the SELECT and return all rows.
     */
    public function get(): array
    {
        [$sql, $binds] = $this->buildSelect();
        $result = $this->fetchAll($sql, $binds);
        $this->resetBuilder();

        return $result;
    }

    /**
     * Execute the SELECT and return the first row.
     */
    public function first(): array|false
    {
        $this->builderLimit = 1;
        [$sql, $binds] = $this->buildSelect();
        $result = $this->fetch($sql, $binds);
        $this->resetBuilder();

        return $result;
    }

    /**
     * Return a COUNT(*).
     */
    public function count(): int
    {
        $savedSelect = $this->builderSelect;
        $this->builderSelect = ['COUNT(*) AS cnt'];
        [$sql, $binds] = $this->buildSelect();
        $row = $this->fetch($sql, $binds);
        $this->builderSelect = $savedSelect;
        $this->resetBuilder();

        return (int) ($row['cnt'] ?? 0);
    }

    /**
     * Check whether at least one row exists.
     */
    public function exists(): bool
    {
        return $this->count() > 0;
    }

    /**
     * Insert a row and return the last insert ID.
     */
    public function insert(array $data): string
    {
        $columns      = array_keys($data);
        $placeholders = array_map(fn (string $c) => ":{$c}", $columns);

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->builderTable,
            implode(', ', $columns),
            implode(', ', $placeholders),
        );

        $binds = [];
        foreach ($data as $key => $value) {
            $binds[":{$key}"] = $value;
        }

        $this->query($sql, $binds);
        $id = $this->lastInsertId();
        $this->resetBuilder();

        return $id;
    }

    /**
     * Update rows matching the current WHERE clauses.
     */
    public function update(array $data): int
    {
        $sets  = [];
        $binds = $this->builderBinds;

        foreach ($data as $column => $value) {
            $placeholder            = ':set_' . $column;
            $sets[]                 = "{$column} = {$placeholder}";
            $binds[$placeholder]    = $value;
        }

        $sql = "UPDATE {$this->builderTable} SET " . implode(', ', $sets);

        if (!empty($this->builderWhere)) {
            $sql .= ' WHERE ' . implode(' AND ', $this->builderWhere);
        }

        $affected = $this->execute($sql, $binds);
        $this->resetBuilder();

        return $affected;
    }

    /**
     * Delete rows matching the current WHERE clauses.
     */
    public function deleteRows(): int
    {
        $sql = "DELETE FROM {$this->builderTable}";

        if (!empty($this->builderWhere)) {
            $sql .= ' WHERE ' . implode(' AND ', $this->builderWhere);
        }

        $affected = $this->execute($sql, $this->builderBinds);
        $this->resetBuilder();

        return $affected;
    }

    /**
     * Paginate results. Returns an array with 'data', 'total', 'per_page',
     * 'current_page', 'last_page'.
     */
    public function paginate(int $perPage = 15, int $currentPage = 1): array
    {
        $total    = $this->cloneForCount()->count();
        $lastPage = (int) max(1, ceil($total / $perPage));

        $currentPage = max(1, min($currentPage, $lastPage));

        $this->builderLimit  = $perPage;
        $this->builderOffset = ($currentPage - 1) * $perPage;

        [$sql, $binds] = $this->buildSelect();
        $data = $this->fetchAll($sql, $binds);
        $this->resetBuilder();

        return [
            'data'         => $data,
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $currentPage,
            'last_page'    => $lastPage,
        ];
    }

    // ------------------------------------------------------------------
    // Internal builder helpers
    // ------------------------------------------------------------------

    /**
     * Build a SELECT SQL string from the current builder state.
     *
     * @return array{0: string, 1: array}
     */
    private function buildSelect(): array
    {
        $sql = 'SELECT ' . implode(', ', $this->builderSelect)
             . ' FROM ' . $this->builderTable;

        if (!empty($this->builderJoin)) {
            $sql .= ' ' . implode(' ', $this->builderJoin);
        }

        if (!empty($this->builderWhere)) {
            $sql .= ' WHERE ' . implode(' AND ', $this->builderWhere);
        }

        if ($this->builderGroup !== null) {
            $sql .= ' GROUP BY ' . $this->builderGroup;
        }

        if ($this->builderHaving !== null) {
            $sql .= ' HAVING ' . $this->builderHaving;
        }

        if (!empty($this->builderOrder)) {
            $sql .= ' ORDER BY ' . implode(', ', $this->builderOrder);
        }

        if ($this->builderLimit !== null) {
            $sql .= ' LIMIT ' . $this->builderLimit;
        }

        if ($this->builderOffset !== null) {
            $sql .= ' OFFSET ' . $this->builderOffset;
        }

        return [$sql, $this->builderBinds];
    }

    /**
     * Clone the current builder state for a COUNT sub-query (preserves WHERE
     * clauses but drops select / order / limit / offset).
     */
    private function cloneForCount(): static
    {
        $clone = clone $this;
        $clone->builderSelect = ['COUNT(*) AS cnt'];
        $clone->builderOrder  = [];
        $clone->builderLimit  = null;
        $clone->builderOffset = null;

        return $clone;
    }

    /**
     * Reset all builder state.
     */
    private function resetBuilder(): void
    {
        $this->builderTable   = '';
        $this->builderSelect  = ['*'];
        $this->builderWhere   = [];
        $this->builderBinds   = [];
        $this->builderOrder   = [];
        $this->builderJoin    = [];
        $this->builderLimit   = null;
        $this->builderOffset  = null;
        $this->builderGroup   = null;
        $this->builderHaving  = null;
    }
}
