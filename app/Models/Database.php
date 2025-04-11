<?php

namespace App\Models;

use PDO;
use PDOException;

class Database {
    private static ?PDO $instance = null;

    // Private constructor to prevent direct instantiation
    private function __construct() {}

    // Prevent cloning
    private function __clone() {}

    // Prevent unserialization
    public function __wakeup() {}

    // Get PDO instance (Singleton pattern)
    public static function getInstance(): PDO {
        if (self::$instance === null) {
            $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Throw exceptions on error
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Fetch associative arrays
                PDO::ATTR_EMULATE_PREPARES   => false,                  // Use native prepared statements
            ];

            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                // Log the error properly in production
                error_log("Database Connection Error: " . $e->getMessage());
                // Show a generic error message to the user
                throw new PDOException("Database connection failed. Please try again later.", (int)$e->getCode());
            }
        }
        return self::$instance;
    }

    // Convenience methods (optional)
    public static function prepare(string $sql): \PDOStatement|false {
        return self::getInstance()->prepare($sql);
    }

    public static function query(string $sql): \PDOStatement|false {
        return self::getInstance()->query($sql);
    }

    public static function lastInsertId(): string|false {
        return self::getInstance()->lastInsertId();
    }
}