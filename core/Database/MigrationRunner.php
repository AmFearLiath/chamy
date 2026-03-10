<?php

declare(strict_types=1);

namespace Chamy\Core\Database;

use RuntimeException;

final class MigrationRunner
{
    private Connection $db;
    private string $migrationsTable;

    public function __construct(Connection $db)
    {
        $this->db = $db;
        $this->migrationsTable = $db->table('migrations');
    }

    public function ensureMigrationsTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->migrationsTable} (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255) NOT NULL,
            batch INT UNSIGNED NOT NULL DEFAULT 1,
            executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_migration (migration)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->db->getPdo()->exec($sql);
    }

    public function getExecuted(): array
    {
        $sql = "SELECT migration FROM {$this->migrationsTable} ORDER BY batch, id";
        return array_column($this->db->fetchAll($sql), 'migration');
    }

    public function getNextBatch(): int
    {
        $sql = "SELECT COALESCE(MAX(batch), 0) + 1 AS next_batch FROM {$this->migrationsTable}";
        return (int) $this->db->fetchColumn($sql);
    }

    public function run(string $migrationsPath): array
    {
        $this->ensureMigrationsTable();

        $executed = $this->getExecuted();
        $batch = $this->getNextBatch();
        $ran = [];

        $files = glob($migrationsPath . '/*.php');
        if ($files === false) {
            return [];
        }

        sort($files);

        foreach ($files as $file) {
            $name = pathinfo($file, PATHINFO_FILENAME);

            if (in_array($name, $executed, true)) {
                continue;
            }

            $migration = require $file;

            if (!is_array($migration) || !isset($migration['up'])) {
                throw new RuntimeException("Migration '{$name}' must return an array with an 'up' key.");
            }

            try {
                if (is_callable($migration['up'])) {
                    ($migration['up'])($this->db);
                } elseif (is_string($migration['up'])) {
                    $this->db->getPdo()->exec($migration['up']);
                }

                $this->db->query(
                    "INSERT INTO {$this->migrationsTable} (migration, batch) VALUES (:migration, :batch)",
                    ['migration' => $name, 'batch' => $batch]
                );

                $ran[] = $name;
            } catch (\Throwable $e) {
                throw new RuntimeException("Migration '{$name}' failed: " . $e->getMessage(), 0, $e);
            }
        }

        return $ran;
    }

    public function rollbackLast(string $migrationsPath): array
    {
        $this->ensureMigrationsTable();

        $sql = "SELECT migration FROM {$this->migrationsTable} WHERE batch = (SELECT MAX(batch) FROM {$this->migrationsTable}) ORDER BY id DESC";
        $migrations = array_column($this->db->fetchAll($sql), 'migration');

        $rolledBack = [];

        foreach ($migrations as $name) {
            $file = $migrationsPath . '/' . $name . '.php';

            if (!file_exists($file)) {
                continue;
            }

            $migration = require $file;

            if (!is_array($migration) || !isset($migration['down'])) {
                continue;
            }

            try {
                if (is_callable($migration['down'])) {
                    ($migration['down'])($this->db);
                } elseif (is_string($migration['down'])) {
                    $this->db->getPdo()->exec($migration['down']);
                }

                $this->db->query(
                    "DELETE FROM {$this->migrationsTable} WHERE migration = :migration",
                    ['migration' => $name]
                );

                $rolledBack[] = $name;
            } catch (\Throwable $e) {
                throw new RuntimeException("Rollback of '{$name}' failed: " . $e->getMessage(), 0, $e);
            }
        }

        return $rolledBack;
    }
}
