<?php

namespace App\Models;

use PDO;
use App\Models\Database;

// Represents a referral commission record generated when a referred user's registration is activated.
class Referral {
    public ?int $id = null;
    public ?int $referrer_user_id = null; // User who referred (gets commission)
    public ?int $referred_user_id = null; // User who was referred
    public ?int $registration_id = null; // The registration that triggered this commission
    public ?float $commission_amount = 0.0;
    public string $status = 'pending'; // pending, paid, cancelled
    public ?string $paid_at = null;
    public ?string $created_at = null;
    public ?string $updated_at = null;

    // Relationships
    public ?User $referrerUser = null;
    public ?User $referredUser = null;
    public ?Registration $registration = null;

    /**
     * Find referral commission record by ID.
     */
    public static function findById(int $id): ?self {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM referrals WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $refData = $stmt->fetch();

        return $refData ? self::hydrate($refData) : null;
    }

    /**
     * Find referral by Registration ID.
     */
    public static function findByRegistrationId(int $registrationId): ?self {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM referrals WHERE registration_id = :reg_id LIMIT 1");
        $stmt->execute(['reg_id' => $registrationId]);
        $refData = $stmt->fetch();

        return $refData ? self::hydrate($refData) : null;
    }

    /**
     * Get referrals (commissions) earned by a specific referrer user.
     */
    public static function findByReferrerId(int $referrerUserId, int $limit = 10, int $offset = 0): array {
        $db = Database::getInstance();
        // Join to get referred user's email
        $sql = "SELECT r.*, u_refd.email as referred_user_email, reg.status as registration_status
                FROM referrals r
                JOIN users u_refd ON r.referred_user_id = u_refd.id
                JOIN registrations reg ON r.registration_id = reg.id
                WHERE r.referrer_user_id = :referrer_id
                ORDER BY r.created_at DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $db->prepare($sql);
        $stmt->bindParam(':referrer_id', $referrerUserId, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $referralsData = $stmt->fetchAll();
        $referrals = [];
        foreach ($referralsData as $data) {
            $referrals[] = self::hydrate($data);
        }
        return $referrals;
    }

     /**
      * Count referrals earned by a specific referrer user.
      */
     public static function countByReferrerId(int $referrerUserId): int {
         $db = Database::getInstance();
         $stmt = $db->prepare("SELECT COUNT(*) FROM referrals WHERE referrer_user_id = :referrer_id");
         $stmt->execute(['referrer_id' => $referrerUserId]);
         return (int) $stmt->fetchColumn();
     }


     /**
      * Get all referral commissions (for admin) with pagination and filtering.
      */
    public static function getAllAdmin(array $filters = [], int $limit = 20, int $offset = 0): array {
        $db = Database::getInstance();
        $sql = "SELECT ref.*,
                       referrer.email as referrer_email, referrer.collaborator_code as referrer_code,
                       referred.email as referred_email,
                       reg.id as reg_display_id,
                       pkg.name as package_name
                FROM referrals ref
                JOIN users referrer ON ref.referrer_user_id = referrer.id
                JOIN users referred ON ref.referred_user_id = referred.id
                JOIN registrations reg ON ref.registration_id = reg.id
                LEFT JOIN packages pkg ON reg.package_id = pkg.id";
        $where = [];
        $params = [];

        if (!empty($filters['search'])) {
            $searchTerm = '%' . $filters['search'] . '%';
            // Search by referrer email/code or referred email
            $where[] = "(referrer.email LIKE :search OR referrer.collaborator_code LIKE :search OR referred.email LIKE :search)";
            $params['search'] = $searchTerm;
        }
        if (!empty($filters['status'])) {
            $where[] = "ref.status = :status";
            $params['status'] = $filters['status'];
        }
         if (!empty($filters['referrer_id'])) {
            $where[] = "ref.referrer_user_id = :referrer_id";
            $params['referrer_id'] = $filters['referrer_id'];
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        $sql .= " ORDER BY ref.created_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $db->prepare($sql);

        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        foreach ($params as $key => &$val) {
             $type = is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindParam(":$key", $val, $type);
        }
        unset($val);

        $stmt->execute();
        $referralsData = $stmt->fetchAll();
        $referrals = [];
        foreach ($referralsData as $data) {
            $referrals[] = self::hydrate($data);
        }
        return $referrals;
    }

    /**
     * Count all referral commissions (for admin) with filtering.
     */
    public static function countAllAdmin(array $filters = []): int {
        $db = Database::getInstance();
        $sql = "SELECT COUNT(ref.id)
                FROM referrals ref
                JOIN users referrer ON ref.referrer_user_id = referrer.id
                JOIN users referred ON ref.referred_user_id = referred.id";
        $where = [];
        $params = [];

         if (!empty($filters['search'])) {
             $searchTerm = '%' . $filters['search'] . '%';
             $where[] = "(referrer.email LIKE :search OR referrer.collaborator_code LIKE :search OR referred.email LIKE :search)";
             $params['search'] = $searchTerm;
         }
         if (!empty($filters['status'])) {
             $where[] = "ref.status = :status";
             $params['status'] = $filters['status'];
         }
          if (!empty($filters['referrer_id'])) {
             $where[] = "ref.referrer_user_id = :referrer_id";
             $params['referrer_id'] = $filters['referrer_id'];
         }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Get total pending commission amount for a specific referrer.
     */
    public static function getTotalPendingCommission(int $referrerUserId): float {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT SUM(commission_amount) FROM referrals WHERE referrer_user_id = :referrer_id AND status = 'pending'");
        $stmt->execute(['referrer_id' => $referrerUserId]);
        return (float) $stmt->fetchColumn();
    }

    /**
      * Get total commission paid (all time or in period for reports).
      */
     public static function getTotalCommissionPaid(string $startDate = null, string $endDate = null): float {
         $db = Database::getInstance();
         $sql = "SELECT SUM(commission_amount) FROM referrals WHERE status = 'paid'";
         $params = [];
         if($startDate) {
             $sql .= " AND paid_at >= :start_date";
             $params['start_date'] = $startDate;
         }
          if($endDate) {
             $sql .= " AND paid_at <= :end_date";
             $params['end_date'] = $endDate . ' 23:59:59'; // Include end date
         }
         $stmt = $db->prepare($sql);
         $stmt->execute($params);
         return (float) $stmt->fetchColumn();
     }


    /**
     * Create a new referral commission record.
     * @return bool|int Returns the new ID on success, false on failure.
     */
    public function create(): bool|int {
        $db = Database::getInstance();
        $sql = "INSERT INTO referrals (referrer_user_id, referred_user_id, registration_id, commission_amount, status, created_at, updated_at)
                VALUES (:referrer_user_id, :referred_user_id, :registration_id, :commission_amount, :status, NOW(), NOW())";
        $stmt = $db->prepare($sql);

        $success = $stmt->execute([
            'referrer_user_id' => $this->referrer_user_id,
            'referred_user_id' => $this->referred_user_id,
            'registration_id' => $this->registration_id,
            'commission_amount' => $this->commission_amount,
            'status' => $this->status ?? 'pending'
        ]);

        if ($success) {
            $this->id = (int)$db->lastInsertId();
            return $this->id;
        }
        return false;
    }

    /**
     * Update referral status (e.g., to 'paid' or 'cancelled').
     */
    public function updateStatus(string $newStatus): bool {
        if (!$this->id) return false;
        $db = Database::getInstance();
        $paidAtSql = ($newStatus === 'paid') ? ', paid_at = NOW()' : '';

        $stmt = $db->prepare("UPDATE referrals SET status = :status $paidAtSql, updated_at = NOW() WHERE id = :id");
        $success = $stmt->execute(['status' => $newStatus, 'id' => $this->id]);

        if ($success) {
            $this->status = $newStatus;
            if ($newStatus === 'paid') {
                $this->paid_at = date('Y-m-d H:i:s');
            }
        }
        return $success;
    }

     /**
      * Mark multiple pending referrals as paid for a specific referrer.
      */
     public static function markAsPaid(int $referrerUserId): bool {
         $db = Database::getInstance();
         $sql = "UPDATE referrals SET status = 'paid', paid_at = NOW(), updated_at = NOW()
                 WHERE referrer_user_id = :referrer_id AND status = 'pending'";
         $stmt = $db->prepare($sql);
         return $stmt->execute(['referrer_id' => $referrerUserId]);
     }


    /**
     * Create Referral object from database data.
     */
    private static function hydrate(array $data): self {
        $ref = new self();
        $ref->id = (int)$data['id'];
        $ref->referrer_user_id = (int)$data['referrer_user_id'];
        $ref->referred_user_id = (int)$data['referred_user_id'];
        $ref->registration_id = (int)$data['registration_id'];
        $ref->commission_amount = (float)$data['commission_amount'];
        $ref->status = $data['status'];
        $ref->paid_at = $data['paid_at'];
        $ref->created_at = $data['created_at'];
        $ref->updated_at = $data['updated_at'];

        // Optionally hydrate related info from joins
        if (isset($data['referrer_email'])) {
            $ref->referrerUser = new User();
            $ref->referrerUser->id = $ref->referrer_user_id;
            $ref->referrerUser->email = $data['referrer_email'];
            $ref->referrerUser->collaborator_code = $data['referrer_code'] ?? null;
        }
         if (isset($data['referred_user_email'])) {
            $ref->referredUser = new User();
            $ref->referredUser->id = $ref->referred_user_id;
            $ref->referredUser->email = $data['referred_user_email'];
        }
        if (isset($data['reg_display_id'])) {
             $ref->registration = new Registration();
             $ref->registration->id = (int)$data['reg_display_id'];
             $ref->registration->status = $data['registration_status'] ?? null; // If joined
             if(isset($data['package_name'])) {
                 $ref->registration->package = new Package();
                 $ref->registration->package->name = $data['package_name'];
             }
         }

        return $ref;
    }
}

// --- Assumed `referrals` table structure ---
/*
CREATE TABLE `referrals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `referrer_user_id` int(11) NOT NULL COMMENT 'FK to users table (who referred)',
  `referred_user_id` int(11) NOT NULL COMMENT 'FK to users table (who was referred)',
  `registration_id` int(11) NOT NULL COMMENT 'FK to registrations table',
  `commission_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('pending','paid','cancelled') NOT NULL DEFAULT 'pending',
  `paid_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `referrer_user_id` (`referrer_user_id`),
  KEY `registration_id` (`registration_id`),
  CONSTRAINT `referrals_ibfk_1` FOREIGN KEY (`referrer_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `referrals_ibfk_2` FOREIGN KEY (`referred_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `referrals_ibfk_3` FOREIGN KEY (`registration_id`) REFERENCES `registrations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
*/