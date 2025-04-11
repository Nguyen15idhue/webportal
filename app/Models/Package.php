<?php

namespace App\Models;

use PDO;
use App\Models\Database;

class Package {
    public ?int $id = null;
    public ?string $name = null;
    public ?float $price = null;
    public ?int $duration_months = null; // 999 for unlimited/lifetime
    public ?array $features = null; // Store as JSON in DB? Or separate table?
    public bool $popular = false;
    public bool $special = false;
    public bool $is_active = true;
    public ?string $created_at = null;
    public ?string $updated_at = null;

    /**
     * Find package by ID.
     */
    public static function findById(int $id): ?self {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM packages WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $packageData = $stmt->fetch();

        return $packageData ? self::hydrate($packageData) : null;
    }

    /**
     * Get all active packages.
     */
    public static function getAllActive(): array {
        $db = Database::getInstance();
        // Order by price or a specific order column if needed
        $stmt = $db->query("SELECT * FROM packages WHERE is_active = 1 ORDER BY price ASC");
        $packagesData = $stmt->fetchAll();
        $packages = [];
        foreach ($packagesData as $data) {
            $packages[] = self::hydrate($data);
        }
        return $packages;
    }

     /**
     * Get all packages (for admin). Add pagination/filtering later.
     */
    public static function getAllAdmin(): array {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT * FROM packages ORDER BY price ASC");
        $packagesData = $stmt->fetchAll();
        $packages = [];
        foreach ($packagesData as $data) {
            $packages[] = self::hydrate($data);
        }
        return $packages;
    }


    // TODO: Add create() and update() methods for admin if needed

    /**
     * Create Package object from database data.
     */
    private static function hydrate(array $data): self {
        $package = new self();
        $package->id = (int)$data['id'];
        $package->name = $data['name'];
        $package->price = (float)$data['price'];
        $package->duration_months = (int)$data['duration_months'];
        // Decode features if stored as JSON
        $package->features = isset($data['features']) ? json_decode($data['features'], true) : [];
        if (json_last_error() !== JSON_ERROR_NONE) {
             $package->features = []; // Handle potential JSON decode error
        }
        $package->popular = (bool)$data['popular'];
        $package->special = (bool)$data['special'];
        $package->is_active = (bool)$data['is_active'];
        $package->created_at = $data['created_at'];
        $package->updated_at = $data['updated_at'];
        return $package;
    }
}

// --- Assumed `packages` table structure ---
/*
CREATE TABLE `packages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `duration_months` int(11) NOT NULL COMMENT '999 for lifetime',
  `features` text DEFAULT NULL COMMENT 'JSON array of feature strings',
  `popular` tinyint(1) NOT NULL DEFAULT 0,
  `special` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
*/