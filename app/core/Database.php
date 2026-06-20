<?php
declare(strict_types=1);

namespace Koonect\Core;

use PDO;
use PDOException;

/**
 * Database — Singleton PDO pour MariaDB
 * Toutes les requêtes passent par des requêtes préparées.
 */
final class Database
{
    private static ?Database $instance = null;
    private PDO $pdo;

    private function __construct()
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            DB_HOST, DB_PORT, DB_NAME
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_STRINGIFY_FETCHES  => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        ];

        try {
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            Logger::error('DB connection failed: ' . $e->getMessage());
            throw new \RuntimeException('Erreur de connexion à la base de données.', 500);
        }
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * Exécute une requête préparée et retourne le statement.
     */
    public function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $paramKey = is_int($key) ? $key + 1 : $key;
            if (is_int($value)) {
                $stmt->bindValue($paramKey, $value, PDO::PARAM_INT);
            } elseif (is_bool($value)) {
                $stmt->bindValue($paramKey, $value, PDO::PARAM_BOOL);
            } elseif ($value === null) {
                $stmt->bindValue($paramKey, null, PDO::PARAM_NULL);
            } else {
                $stmt->bindValue($paramKey, (string)$value, PDO::PARAM_STR);
            }
        }
        $stmt->execute();
        return $stmt;
    }

    /**
     * Retourne une seule ligne.
     */
    public function fetch(string $sql, array $params = []): array|false
    {
        return $this->query($sql, $params)->fetch();
    }

    /**
     * Retourne toutes les lignes.
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    /**
     * Insert et retourne le dernier ID inséré.
     */
    public function insert(string $sql, array $params = []): string|false
    {
        $this->query($sql, $params);
        return $this->pdo->lastInsertId();
    }

    /**
     * Execute (UPDATE/DELETE) et retourne le nombre de lignes affectées.
     */
    public function execute(string $sql, array $params = []): int
    {
        return $this->query($sql, $params)->rowCount();
    }

    public function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
    }

    public function commit(): void
    {
        $this->pdo->commit();
    }

    public function rollback(): void
    {
        $this->pdo->rollBack();
    }

    public function lastInsertId(): string|false
    {
        return $this->pdo->lastInsertId();
    }

    // Empêcher le clonage et la désérialisation
    private function __clone() {}
    public function __wakeup(): never
    {
        throw new \RuntimeException('Cannot unserialize singleton.');
    }
}
