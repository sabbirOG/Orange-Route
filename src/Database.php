<?php

namespace OrangeRoute;

use PDO;

/**
 * Simplified Database - Mobile-first, fast queries
 */
class Database
{
    private static ?PDO $conn = null;
    
    public static function tableExists(string $table): bool
    {
        $sql = "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?";
        $count = (int) self::fetchValue($sql, [$table]);
        return $count > 0;
    }
    
    public static function get(): PDO
    {
        if (!self::$conn) {
            $dsn = "mysql:host=" . ($_ENV['DB_HOST'] ?? 'localhost') .
                   ";dbname=" . ($_ENV['DB_DATABASE'] ?? 'orangeroute') .
                   ";charset=utf8mb4";
            try {
                self::$conn = new PDO($dsn, $_ENV['DB_USERNAME'] ?? 'root', $_ENV['DB_PASSWORD'] ?? '', [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]);
            } catch (\PDOException $e) {
                if (($_ENV['APP_DEBUG'] ?? 'false') === 'true') {
                    die('Database connection failed: ' . e($e->getMessage()));
                }
                // In production, rethrow to be handled upstream
                throw $e;
            }
        }
        return self::$conn;
    }
    
    public static function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = self::get()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    
    public static function execute(string $sql, array $params = []): int
    {
        $stmt = self::query($sql, $params);
        return $stmt->rowCount();
    }
    
    public static function fetch(string $sql, array $params = []): ?array
    {
        return self::query($sql, $params)->fetch() ?: null;
    }
    
    public static function fetchOne(string $sql, array $params = []): ?array
    {
        return self::fetch($sql, $params);
    }
    
    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }
    
    public static function fetchValue(string $sql, array $params = [])
    {
        $result = self::fetch($sql, $params);
        return $result ? reset($result) : null;
    }
    
    public static function insert(string $sql, array $params = []): int
    {
        self::query($sql, $params);
        return (int) self::get()->lastInsertId();
    }
    
    public static function lastInsertId(): string
    {
        return self::get()->lastInsertId();
    }
    
    public static function beginTransaction(): void
    {
        self::get()->beginTransaction();
    }
    
    public static function commit(): void
    {
        self::get()->commit();
    }
    
    public static function rollback(): void
    {
        if (self::get()->inTransaction()) {
            self::get()->rollBack();
        }
    }
}
