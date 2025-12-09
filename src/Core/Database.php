<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use Psr\Log\LoggerInterface;

/**
 * Simple PDO wrapper focused on prepared statements and safe usage.
 * - Use fetch() to get a single row (associative)
 * - Use fetchAll() to get multiple rows
 * - Use fetchColumn() to get a single scalar value
 * - Use execute() for INSERT/UPDATE/DELETE (returns affected rows or last insert id)
 * - Use explain() to run EXPLAIN on SELECT queries (returns explain rows)
 *
 * This class intentionally avoids convenience methods that encourage SELECT * usage.
 */
class Database
{
    private PDO $pdo;
    private ?LoggerInterface $logger;

    /**
     * @param string $host
     * @param int $port
     * @param string $dbname
     * @param string $user
     * @param string $pass
     * @param LoggerInterface|null $logger Optional PSR-3 logger for debugging (Monolog)
     *
     * @throws PDOException on connection failure
     */
    public function __construct(
        string $host,
        int $port,
        string $dbname,
        string $user,
        string $pass,
        ?LoggerInterface $logger = null
    ) {
        $this->logger = $logger;
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, $port, $dbname);

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false, // use native prepared statements when possible
        ];

        $this->pdo = new PDO($dsn, $user, $pass, $options);
    }

    /**
     * Expose the underlying PDO when you need advanced features.
     */
    public function pdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * Fetch a single row (associative) or null if not found.
     *
     * @param string $sql SQL with named or positional placeholders (no SELECT *)
     * @param array $params Bound parameters
     */
    public function fetch(string $sql, array $params = []): ?array
    {
        $stmt = $this->pdo->prepare($sql);
        $this->bindAndExecute($stmt, $params);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    /**
     * Fetch multiple rows as an array.
     *
     * @param string $sql
     * @param array $params
     * @return array<int,array<string,mixed>>
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->pdo->prepare($sql);
        $this->bindAndExecute($stmt, $params);
        return $stmt->fetchAll();
    }

    /**
     * Fetch a single column value (e.g., COUNT(*)).
     *
     * @param string $sql
     * @param array $params
     * @return mixed
     */
    public function fetchColumn(string $sql, array $params = [])
    {
        $stmt = $this->pdo->prepare($sql);
        $this->bindAndExecute($stmt, $params);
        return $stmt->fetchColumn();
    }

    /**
     * Execute an INSERT/UPDATE/DELETE. Returns number of affected rows.
     * For INSERT you can call lastInsertId() afterwards.
     *
     * @param string $sql
     * @param array $params
     * @return int affected rows
     */
    public function execute(string $sql, array $params = []): int
    {
        $stmt = $this->pdo->prepare($sql);
        $this->bindAndExecute($stmt, $params);
        $count = $stmt->rowCount();

        // Some drivers return 0 for inserts; lastInsertId is authoritative for new rows
        return $count;
    }

    /**
     * Get last insert id (as int). Returns 0 if none.
     */
    public function lastInsertId(): int
    {
        $id = $this->pdo->lastInsertId();
        return $id === '' ? 0 : (int)$id;
    }

    /**
     * Begin transaction.
     */
    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * Commit transaction.
     */
    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    /**
     * Roll back transaction.
     */
    public function rollBack(): bool
    {
        return $this->pdo->rollBack();
    }

    /**
     * Run EXPLAIN on a SELECT query. Returns array of rows returned by EXPLAIN.
     * This helper will automatically prefix "EXPLAIN " and bind params.
     *
     * Use in README to show EXPLAIN output for a representative SELECT.
     *
     * Example:
     * $explainRows = $db->explain("SELECT id, title FROM products WHERE category = :c ORDER BY created_at DESC LIMIT :l", ['c'=>'electronics', ':l'=>10]);
     *
     * IMPORTANT: When using LIMIT/OFFSET with bound params, bind with PDO::PARAM_INT.
     *
     * @param string $selectSql The SELECT SQL (without EXPLAIN)
     * @param array $params
     * @return array<int,array<string,mixed>>
     */
    public function explain(string $selectSql, array $params = []): array
    {
        $sql = 'EXPLAIN ' . $selectSql;
        $stmt = $this->pdo->prepare($sql);
        $this->bindAndExecute($stmt, $params);
        return $stmt->fetchAll();
    }

    /**
     * Helper to bind values (detect ints vs strings) and execute the prepared statement.
     *
     * Note: For integer pagination params, pass them as PHP ints so they are bound as PARAM_INT.
     *
     * @param \PDOStatement $stmt
     * @param array $params
     */
    private function bindAndExecute(\PDOStatement $stmt, array $params = []): void
    {
        foreach ($params as $key => $value) {
            // allow both named (:name) and positional (0-based) params
            if (is_int($value)) {
                $stmt->bindValue(is_int($key) ? $key + 1 : $key, $value, PDO::PARAM_INT);
            } elseif (is_bool($value)) {
                $stmt->bindValue(is_int($key) ? $key + 1 : $key, $value, PDO::PARAM_BOOL);
            } elseif ($value === null) {
                $stmt->bindValue(is_int($key) ? $key + 1 : $key, null, PDO::PARAM_NULL);
            } else {
                $stmt->bindValue(is_int($key) ? $key + 1 : $key, (string)$value, PDO::PARAM_STR);
            }
        }

        // Execute (exceptions bubble up - caller may catch)
        $stmt->execute();
    }

    /**
     * Utility to run a safe SELECT with explicit columns (discourages SELECT *).
     * Example:
     * $rows = $db->safeSelect('users', ['id','name','email'], 'WHERE city = :city ORDER BY created_at DESC LIMIT :l OFFSET :o', [':city'=>'Delhi', ':l'=>10, ':o'=>0]);
     *
     * @param string $table
     * @param array $columns
     * @param string $whereAndOtherClause
     * @param array $params
     * @return array<int,array<string,mixed>>
     */
    public function safeSelect(string $table, array $columns, string $whereAndOtherClause = '', array $params = []): array
    {
        // Basic table/column validation to avoid injection via parameters:
        // - table must be alphanumeric + underscore
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            throw new PDOException('Invalid table name');
        }

        foreach ($columns as $col) {
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $col)) {
                throw new PDOException('Invalid column name: ' . $col);
            }
        }

        $sql = 'SELECT ' . implode(',', $columns) . ' FROM ' . $table . ' ' . $whereAndOtherClause;
        $stmt = $this->pdo->prepare($sql);
        $this->bindAndExecute($stmt, $params);
        return $stmt->fetchAll();
    }
}
