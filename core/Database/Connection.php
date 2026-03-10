<?php

declare(strict_types=1);

namespace Chamy\Core\Database;

use PDO;
use PDOException;
use RuntimeException;

final class Connection
{
    private ?PDO $pdo = null;

    private string $driver;
    private string $host;
    private int $port;
    private string $database;
    private string $username;
    private string $password;
    private string $charset;
    private string $collation;
    private string $prefix;

    public function __construct(
        string $driver,
        string $host,
        int $port,
        string $database,
        string $username,
        string $password,
        string $charset = 'utf8mb4',
        string $collation = 'utf8mb4_unicode_ci',
        string $prefix = 'chamy_'
    ) {
        $this->driver = $driver;
        $this->host = $host;
        $this->port = $port;
        $this->database = $database;
        $this->username = $username;
        $this->password = $password;
        $this->charset = $charset;
        $this->collation = $collation;
        $this->prefix = $prefix;
    }

    public function getPdo(): PDO
    {
        if ($this->pdo === null) {
            $this->connect();
        }

        return $this->pdo;
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }

    public function table(string $name): string
    {
        return $this->prefix . $name;
    }

    public function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = $this->getPdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function fetchOne(string $sql, array $params = []): ?array
    {
        $result = $this->query($sql, $params)->fetch(PDO::FETCH_ASSOC);
        return $result !== false ? $result : null;
    }

    public function fetchColumn(string $sql, array $params = [], int $column = 0): mixed
    {
        return $this->query($sql, $params)->fetchColumn($column);
    }

    public function insert(string $table, array $data): int
    {
        $columns = array_keys($data);
        $placeholders = array_map(fn(string $col) => ':' . $col, $columns);

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->table($table),
            implode(', ', array_map(fn(string $c) => '`' . $c . '`', $columns)),
            implode(', ', $placeholders)
        );

        $this->query($sql, $data);

        return (int) $this->getPdo()->lastInsertId();
    }

    public function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $sets = [];
        $params = [];

        foreach ($data as $column => $value) {
            $paramKey = 'set_' . $column;
            $sets[] = '`' . $column . '` = :' . $paramKey;
            $params[$paramKey] = $value;
        }

        $sql = sprintf(
            'UPDATE %s SET %s WHERE %s',
            $this->table($table),
            implode(', ', $sets),
            $where
        );

        $params = array_merge($params, $whereParams);

        return $this->query($sql, $params)->rowCount();
    }

    public function delete(string $table, string $where, array $params = []): int
    {
        $sql = sprintf('DELETE FROM %s WHERE %s', $this->table($table), $where);
        return $this->query($sql, $params)->rowCount();
    }

    public function beginTransaction(): void
    {
        $this->getPdo()->beginTransaction();
    }

    public function commit(): void
    {
        $this->getPdo()->commit();
    }

    public function rollBack(): void
    {
        $this->getPdo()->rollBack();
    }

    public function transaction(callable $callback): mixed
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

    public function getDatabase(): string
    {
        return $this->database;
    }

    // ------------------------------------------------------------------

    private function connect(): void
    {
        $dsn = match ($this->driver) {
            'mysql' => "mysql:host={$this->host};port={$this->port};dbname={$this->database};charset={$this->charset}",
            'sqlite' => "sqlite:{$this->database}",
            default => throw new RuntimeException("Unsupported database driver: {$this->driver}"),
        };

        try {
            $this->pdo = new PDO($dsn, $this->username, $this->password, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_STRINGIFY_FETCHES  => false,
            ]);

            if ($this->driver === 'mysql') {
                $this->pdo->exec("SET NAMES '{$this->charset}' COLLATE '{$this->collation}'");
                $this->pdo->exec("SET SESSION sql_mode = 'STRICT_ALL_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");
            }
        } catch (PDOException $e) {
            throw new RuntimeException('Database connection failed: ' . $e->getMessage(), 0, $e);
        }
    }
}
