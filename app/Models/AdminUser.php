<?php

namespace App\Models;

use PDO;
use App\Models\Database;

class AdminUser {
    public ?int $id = null;
    public ?string $name = null;
    public ?string $email = null;
    public ?string $password = null; // Input password (will be hashed)
    public ?string $password_hash = null; // Stored hash
    public ?string $role = null; // e.g., 'SuperAdmin', 'Admin', 'Operator'
    public ?string $phone = null;
    public string $status = 'active'; // Default status
    public ?string $created_at = null;
    public ?string $updated_at = null;

    /**
     * Find admin user by email.
     */
    public static function findByEmail(string $email): ?self {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM admin_users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        $adminData = $stmt->fetch();

        return $adminData ? self::hydrate($adminData) : null;
    }

    /**
     * Find admin user by ID.
     */
    public static function findById(int $id): ?self {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM admin_users WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $adminData = $stmt->fetch();

        return $adminData ? self::hydrate($adminData) : null;
    }

    /**
     * Get all admin users (consider filtering by role/status).
     */
    public static function getAll(int $limit = 20, int $offset = 0): array {
        $db = Database::getInstance();
        // TODO: Add filtering if needed
        $stmt = $db->prepare("SELECT * FROM admin_users ORDER BY name ASC LIMIT :limit OFFSET :offset");
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $adminsData = $stmt->fetchAll();
        $admins = [];
        foreach ($adminsData as $data) {
            $admins[] = self::hydrate($data);
        }
        return $admins;
    }

    /**
     * Count all admin users.
     */
    public static function countAll(): int {
        $db = Database::getInstance();
        // TODO: Add filtering if needed
        $stmt = $db->query("SELECT COUNT(*) FROM admin_users");
        return (int) $stmt->fetchColumn();
    }


    /**
     * Create a new admin user record.
     * Assumes $this->password contains the plain password.
     */
    public function create(): bool|string {
         if (empty($this->password)) {
             throw new \InvalidArgumentException("Password is required to create an admin user.");
         }
         $db = Database::getInstance();
         $sql = "INSERT INTO admin_users (name, email, password_hash, role, phone, status, created_at, updated_at)
                 VALUES (:name, :email, :password_hash, :role, :phone, :status, NOW(), NOW())";
         $stmt = $db->prepare($sql);

         $hashedPassword = password_hash($this->password, PASSWORD_DEFAULT);

         $success = $stmt->execute([
             'name' => $this->name,
             'email' => $this->email,
             'password_hash' => $hashedPassword,
             'role' => $this->role,
             'phone' => $this->phone,
             'status' => $this->status
         ]);

         if ($success) {
             $this->id = (int)$db->lastInsertId();
             // Clear plain password after hashing and saving
             $this->password = null;
             $this->password_hash = $hashedPassword;
             return $this->id;
         }
         return false;
    }

    /**
     * Update admin user data. Only updates non-null properties.
     * If $this->password is set, it will be hashed and updated.
     */
    public function update(): bool {
        if (!$this->id) return false;

        $db = Database::getInstance();
        $fields = [];
        $params = ['id' => $this->id];

        $allowedFields = ['name', 'phone', 'role', 'status']; // Email usually not updatable directly

        foreach ($allowedFields as $field) {
            if ($this->$field !== null) {
                $fields[] = "`$field` = :$field";
                $params[$field] = $this->$field;
            }
        }

        // Only update password if a new one is provided
        if (!empty($this->password)) {
            $fields[] = "`password_hash` = :password_hash";
            $params['password_hash'] = password_hash($this->password, PASSWORD_DEFAULT);
        }

        if (empty($fields)) return true; // Nothing to update

        $fields[] = "`updated_at` = NOW()";
        $setClause = implode(', ', $fields);

        $sql = "UPDATE admin_users SET $setClause WHERE id = :id";
        $stmt = $db->prepare($sql);
        $success = $stmt->execute($params);

        if ($success && !empty($this->password)) {
             $this->password = null; // Clear plain password after update
        }
        return $success;
    }

    /**
     * Delete an admin user.
     */
    public static function delete(int $id): bool {
        if ($id === 1) return false; // Prevent deleting super admin ID 1 (example)
        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM admin_users WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Create AdminUser object from database data.
     */
    private static function hydrate(array $data): self {
        $admin = new self();
        $admin->id = (int)$data['id'];
        $admin->name = $data['name'];
        $admin->email = $data['email'];
        $admin->password_hash = $data['password_hash'];
        $admin->role = $data['role'];
        $admin->phone = $data['phone'];
        $admin->status = $data['status'];
        $admin->created_at = $data['created_at'];
        $admin->updated_at = $data['updated_at'];
        return $admin;
    }
}