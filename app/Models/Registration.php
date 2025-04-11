<?php

namespace App\Models;

use PDO;
use App\Models\Database;

class Registration {
    // Use int for ID, controllers can prefix if needed for display
    public ?int $id = null;
    public ?int $user_id = null;
    public ?int $package_id = null;
    public ?int $location_id = null; // Assuming Location model/table exists
    public ?int $station_id = null; // Assigned by admin after approval
    public ?int $num_account = 1;
    public ?string $start_date = null; // YYYY-MM-DD
    public ?string $end_date = null;   // YYYY-MM-DD or null for lifetime
    public ?float $base_price = 0.0;
    public ?float $vat_percent = 0.0;
    public ?float $vat_amount = 0.0;
    public ?float $total_price = 0.0;
    public ?string $status = 'pending_payment'; // pending_payment, pending_confirmation, active, rejected, expired
    public ?int $collaborator_id = null; // ID of the user who referred (if any)
    public ?string $collaborator_code_used = null; // The actual code entered by user
    public ?string $created_at = null;
    public ?string $updated_at = null;

    // Relationships (Load related objects if needed)
    public ?User $user = null;
    public ?Package $package = null;
    // public ?Location $location = null;
    // public ?Station $station = null; // Assuming Station model

    /**
     * Find registration by ID.
     */
    public static function findById(int $id): ?self {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM registrations WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $regData = $stmt->fetch();

        return $regData ? self::hydrate($regData) : null;
    }

    /**
     * Find registration by ID and User ID (Security Check).
     */
    public static function findByIdAndUserId(int $id, int $userId): ?self {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM registrations WHERE id = :id AND user_id = :user_id LIMIT 1");
        $stmt->execute(['id' => $id, 'user_id' => $userId]);
        $regData = $stmt->fetch();

        return $regData ? self::hydrate($regData) : null;
    }

    /**
     * Get registrations for a specific user with pagination.
     */
    public static function findByUserId(int $userId, int $limit = 10, int $offset = 0): array {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM registrations WHERE user_id = :user_id ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $registrationsData = $stmt->fetchAll();
        $registrations = [];
        foreach ($registrationsData as $data) {
            $registrations[] = self::hydrate($data);
        }
        return $registrations;
    }

    /**
     * Count registrations for a specific user.
     */
    public static function countByUserId(int $userId): int {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT COUNT(*) FROM registrations WHERE user_id = :user_id");
        $stmt->execute(['user_id' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Get all registrations (for admin) with pagination and filtering.
     * @param array $filters ['search' => string, 'status' => string, 'package_id' => int]
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public static function getAllAdmin(array $filters = [], int $limit = 20, int $offset = 0): array {
        $db = Database::getInstance();
        // Join with users table for searching email
        $sql = "SELECT r.*, u.email as user_email
                FROM registrations r
                LEFT JOIN users u ON r.user_id = u.id";
        $where = [];
        $params = [];

        if (!empty($filters['search'])) {
            $searchTerm = '%' . $filters['search'] . '%';
             // Search by registration ID or user email
            $where[] = "(CAST(r.id AS CHAR) LIKE :search OR u.email LIKE :search)";
            $params['search'] = $searchTerm;
        }
        if (!empty($filters['status'])) {
            $where[] = "r.status = :status";
            $params['status'] = $filters['status'];
        }
         if (!empty($filters['package_id'])) {
            $where[] = "r.package_id = :package_id";
            $params['package_id'] = $filters['package_id'];
        }
        // Add more filters (date range, location_id etc.)

        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        $sql .= " ORDER BY r.created_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $db->prepare($sql);

        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        foreach ($params as $key => &$val) {
            $type = is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindParam(":$key", $val, $type);
        }
        unset($val);

        $stmt->execute();
        $regData = $stmt->fetchAll();
        $registrations = [];
        foreach ($regData as $data) {
            $registrations[] = self::hydrate($data);
        }
        return $registrations;
    }

    /**
     * Count all registrations (for admin) with filtering.
      * @param array $filters ['search' => string, 'status' => string, 'package_id' => int]
     * @return int
     */
    public static function countAllAdmin(array $filters = []): int {
        $db = Database::getInstance();
        $sql = "SELECT COUNT(r.id)
                FROM registrations r
                LEFT JOIN users u ON r.user_id = u.id";
        $where = [];
        $params = [];

         if (!empty($filters['search'])) {
            $searchTerm = '%' . $filters['search'] . '%';
            $where[] = "(CAST(r.id AS CHAR) LIKE :search OR u.email LIKE :search)";
            $params['search'] = $searchTerm;
        }
        if (!empty($filters['status'])) {
            $where[] = "r.status = :status";
            $params['status'] = $filters['status'];
        }
         if (!empty($filters['package_id'])) {
            $where[] = "r.package_id = :package_id";
            $params['package_id'] = $filters['package_id'];
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Create a new registration record.
     * @return bool|int Returns the new ID on success, false on failure.
     */
    public function create(): bool|int {
        $db = Database::getInstance();
        $sql = "INSERT INTO registrations (user_id, package_id, location_id, station_id, num_account, start_date, end_date, base_price, vat_percent, vat_amount, total_price, status, collaborator_id, collaborator_code_used, created_at, updated_at)
                VALUES (:user_id, :package_id, :location_id, :station_id, :num_account, :start_date, :end_date, :base_price, :vat_percent, :vat_amount, :total_price, :status, :collaborator_id, :collaborator_code_used, NOW(), NOW())";
        $stmt = $db->prepare($sql);

        $success = $stmt->execute([
            'user_id' => $this->user_id,
            'package_id' => $this->package_id,
            'location_id' => $this->location_id,
            'station_id' => $this->station_id, // Can be null initially
            'num_account' => $this->num_account,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'base_price' => $this->base_price,
            'vat_percent' => $this->vat_percent,
            'vat_amount' => $this->vat_amount,
            'total_price' => $this->total_price,
            'status' => $this->status ?? 'pending_payment',
            'collaborator_id' => $this->collaborator_id,
            'collaborator_code_used' => $this->collaborator_code_used
        ]);

        if ($success) {
            $this->id = (int)$db->lastInsertId();
            return $this->id;
        }
        return false;
    }

    /**
     * Update registration status.
     */
    public function updateStatus(string $newStatus): bool {
        if (!$this->id) return false;
        $db = Database::getInstance();
        $stmt = $db->prepare("UPDATE registrations SET status = :status, updated_at = NOW() WHERE id = :id");
        $success = $stmt->execute(['status' => $newStatus, 'id' => $this->id]);
        if ($success) {
            $this->status = $newStatus;
        }
        return $success;
    }

    /**
     * Update registration details (e.g., assigned station_id, status by admin).
     */
    public function update(): bool {
        if (!$this->id) return false;
        $db = Database::getInstance();
        // Example: Update status and station_id
        $sql = "UPDATE registrations SET
                   status = :status,
                   station_id = :station_id,
                   -- Add other fields if needed (e.g., end_date for renewals)
                   updated_at = NOW()
                WHERE id = :id";
        $stmt = $db->prepare($sql);
        $success = $stmt->execute([
            'status' => $this->status,
            'station_id' => $this->station_id,
            'id' => $this->id
        ]);
         if ($success) {
             // Update object properties if needed
         }
        return $success;
    }

    /**
     * Create Registration object from database data.
     */
    private static function hydrate(array $data): self {
        $reg = new self();
        $reg->id = (int)$data['id'];
        $reg->user_id = (int)$data['user_id'];
        $reg->package_id = (int)$data['package_id'];
        $reg->location_id = (int)$data['location_id'];
        $reg->station_id = isset($data['station_id']) ? (int)$data['station_id'] : null;
        $reg->num_account = (int)$data['num_account'];
        $reg->start_date = $data['start_date'];
        $reg->end_date = $data['end_date'];
        $reg->base_price = (float)$data['base_price'];
        $reg->vat_percent = (float)$data['vat_percent'];
        $reg->vat_amount = (float)$data['vat_amount'];
        $reg->total_price = (float)$data['total_price'];
        $reg->status = $data['status'];
        $reg->collaborator_id = isset($data['collaborator_id']) ? (int)$data['collaborator_id'] : null;
        $reg->collaborator_code_used = $data['collaborator_code_used'];
        $reg->created_at = $data['created_at'];
        $reg->updated_at = $data['updated_at'];

         // Optionally hydrate related user email from join if available
         if (isset($data['user_email'])) {
             $reg->user = new User(); // Create a minimal User object
             $reg->user->id = $reg->user_id;
             $reg->user->email = $data['user_email'];
         }

        return $reg;
    }
}