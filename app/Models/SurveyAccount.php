<?php

namespace App\Models;

use PDO;
use App\Models\Database;

class SurveyAccount {
    public ?int $id = null; // Auto-increment ID is simpler
    public ?int $registration_id = null;
    public ?string $username_acc = null;
    public ?string $password_acc = null; // Consider storing plain text if system-generated and non-critical, or hash if needed
    public ?string $caster_ip = null; // Usually comes from Station
    public ?string $caster_port = null; // Usually comes from Station
    public ?string $mount_point = null; // Usually comes from Station
    public bool $active = true;
    public ?string $created_at = null;
    public ?string $updated_at = null;

    // Relationship
    public ?Registration $registration = null;

    /**
     * Find survey account by ID.
     */
    public static function findById(int $id): ?self {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM survey_accounts WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $accData = $stmt->fetch();

        return $accData ? self::hydrate($accData) : null;
    }

    /**
     * Find survey accounts by Registration ID.
     * @return array<SurveyAccount>
     */
    public static function findByRegistrationId(int $registrationId): array {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM survey_accounts WHERE registration_id = :reg_id ORDER BY created_at ASC");
        $stmt->execute(['reg_id' => $registrationId]);
        $accountsData = $stmt->fetchAll();
        $accounts = [];
        foreach ($accountsData as $data) {
            $accounts[] = self::hydrate($data);
        }
        return $accounts;
    }

     /**
      * Get all survey accounts (for admin) with pagination and filtering.
      * @param array $filters ['search' => string, 'status' => bool|null, 'registration_id' => int|null]
      * @param int $limit
      * @param int $offset
      * @return array
      */
     public static function getAllAdmin(array $filters = [], int $limit = 20, int $offset = 0): array {
         $db = Database::getInstance();
         $sql = "SELECT sa.*, r.id as reg_display_id, u.email as user_email, pkg.name as package_name
                 FROM survey_accounts sa
                 JOIN registrations r ON sa.registration_id = r.id
                 JOIN users u ON r.user_id = u.id
                 LEFT JOIN packages pkg ON r.package_id = pkg.id"; // Join packages too
         $where = [];
         $params = [];

         if (!empty($filters['search'])) {
             $searchTerm = '%' . $filters['search'] . '%';
             // Search by username, registration ID, or user email
             $where[] = "(sa.username_acc LIKE :search OR CAST(r.id AS CHAR) LIKE :search OR u.email LIKE :search)";
             $params['search'] = $searchTerm;
         }
         if (isset($filters['status'])) { // Expecting true or false
             $where[] = "sa.active = :status";
             $params['status'] = (int)$filters['status'];
         }
         if (!empty($filters['registration_id'])) {
             $where[] = "sa.registration_id = :reg_id";
             $params['reg_id'] = $filters['registration_id'];
         }
         // Add package_id filter using r.package_id if needed
         // Add expired filter based on r.end_date and r.status if needed


         if (!empty($where)) {
             $sql .= " WHERE " . implode(' AND ', $where);
         }

         $sql .= " ORDER BY sa.created_at DESC LIMIT :limit OFFSET :offset";
         $stmt = $db->prepare($sql);

         $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
         $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
         foreach ($params as $key => &$val) {
             $type = is_int($val) ? PDO::PARAM_INT : (is_bool($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
             $stmt->bindParam(":$key", $val, $type);
         }
         unset($val);

         $stmt->execute();
         $accountsData = $stmt->fetchAll();
         $accounts = [];
         foreach ($accountsData as $data) {
             $accounts[] = self::hydrate($data);
         }
         return $accounts;
     }

     /**
      * Count all survey accounts (for admin) with filtering.
       * @param array $filters ['search' => string, 'status' => bool|null, 'registration_id' => int|null]
      * @return int
      */
     public static function countAllAdmin(array $filters = []): int {
         $db = Database::getInstance();
         $sql = "SELECT COUNT(sa.id)
                 FROM survey_accounts sa
                 JOIN registrations r ON sa.registration_id = r.id
                 JOIN users u ON r.user_id = u.id";
         $where = [];
         $params = [];

         if (!empty($filters['search'])) {
             $searchTerm = '%' . $filters['search'] . '%';
             $where[] = "(sa.username_acc LIKE :search OR CAST(r.id AS CHAR) LIKE :search OR u.email LIKE :search)";
             $params['search'] = $searchTerm;
         }
         if (isset($filters['status'])) {
             $where[] = "sa.active = :status";
             $params['status'] = (int)$filters['status'];
         }
          if (!empty($filters['registration_id'])) {
             $where[] = "sa.registration_id = :reg_id";
             $params['reg_id'] = $filters['registration_id'];
         }

         if (!empty($where)) {
             $sql .= " WHERE " . implode(' AND ', $where);
         }

         $stmt = $db->prepare($sql);
         $stmt->execute($params);
         return (int) $stmt->fetchColumn();
     }

    /**
     * Create a new survey account record.
     * @return bool|int Returns the new ID on success, false on failure.
     */
    public function create(): bool|int {
        $db = Database::getInstance();
        $sql = "INSERT INTO survey_accounts (registration_id, username_acc, password_acc, caster_ip, caster_port, mount_point, active, created_at, updated_at)
                VALUES (:registration_id, :username_acc, :password_acc, :caster_ip, :caster_port, :mount_point, :active, NOW(), NOW())";
        $stmt = $db->prepare($sql);

        // TODO: Consider hashing password_acc if needed
        $passwordToStore = $this->password_acc; // Or hash($this->password_acc)

        $success = $stmt->execute([
            'registration_id' => $this->registration_id,
            'username_acc' => $this->username_acc,
            'password_acc' => $passwordToStore,
            'caster_ip' => $this->caster_ip,
            'caster_port' => $this->caster_port,
            'mount_point' => $this->mount_point,
            'active' => (int)$this->active
        ]);

        if ($success) {
            $this->id = (int)$db->lastInsertId();
            return $this->id;
        }
        return false;
    }

    /**
     * Update survey account details (e.g., password, active status).
     */
    public function update(): bool {
        if (!$this->id) return false;
        $db = Database::getInstance();
        $sql = "UPDATE survey_accounts SET
                   password_acc = :password_acc,
                   active = :active,
                   updated_at = NOW()
                WHERE id = :id";
        $stmt = $db->prepare($sql);

         // TODO: Consider hashing password_acc if needed
         $passwordToStore = $this->password_acc;

        $success = $stmt->execute([
            'password_acc' => $passwordToStore,
            'active' => (int)$this->active,
            'id' => $this->id
        ]);
        return $success;
    }

    /**
     * Delete survey accounts associated with a registration ID.
     */
    public static function deleteByRegistrationId(int $registrationId): bool {
        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM survey_accounts WHERE registration_id = :reg_id");
        return $stmt->execute(['reg_id' => $registrationId]);
    }

    /**
     * Create SurveyAccount object from database data.
     */
    private static function hydrate(array $data): self {
        $account = new self();
        $account->id = (int)$data['id'];
        $account->registration_id = (int)$data['registration_id'];
        $account->username_acc = $data['username_acc'];
        $account->password_acc = $data['password_acc']; // Raw password from DB
        $account->caster_ip = $data['caster_ip'];
        $account->caster_port = $data['caster_port'];
        $account->mount_point = $data['mount_point'];
        $account->active = (bool)$data['active'];
        $account->created_at = $data['created_at'];
        $account->updated_at = $data['updated_at'];

        // Optionally hydrate related data from joins
         if (isset($data['reg_display_id'])) {
             $account->registration = new Registration();
             $account->registration->id = (int)$data['reg_display_id'];
             if(isset($data['user_email'])) {
                 $account->registration->user = new User();
                 $account->registration->user->email = $data['user_email'];
             }
              if(isset($data['package_name'])) {
                 $account->registration->package = new Package();
                 $account->registration->package->name = $data['package_name'];
             }
         }


        return $account;
    }
}