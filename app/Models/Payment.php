<?php

namespace App\Models;

use PDO;
use App\Models\Database;

class Payment {
    public ?int $id = null; // Use int ID
    public ?int $registration_id = null;
    public ?string $payment_image = null; // Relative path to uploaded file
    public bool $issue_invoice = false;
    public ?string $invoice_info_snapshot = null; // JSON string
    public bool $confirmed = false;
    public ?string $confirmed_at = null;
    public ?string $rejection_reason = null;
    public ?int $confirmed_by_admin_id = null; // Admin who confirmed/rejected
    public ?string $created_at = null;
    public ?string $updated_at = null;

    // Relationships
    public ?Registration $registration = null;
    public ?AdminUser $confirmedByAdmin = null;

    /**
     * Find payment by ID.
     */
    public static function findById(int $id): ?self {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM payments WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $paymentData = $stmt->fetch();

        return $paymentData ? self::hydrate($paymentData) : null;
    }

    /**
     * Find payment by Registration ID.
     */
    public static function findByRegistrationId(int $registrationId): ?self {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM payments WHERE registration_id = :reg_id ORDER BY created_at DESC LIMIT 1");
        $stmt->execute(['reg_id' => $registrationId]);
        $paymentData = $stmt->fetch();

        return $paymentData ? self::hydrate($paymentData) : null;
    }

    /**
     * Get all payments (for admin) with pagination and filtering.
     */
    public static function getAllAdmin(array $filters = [], int $limit = 20, int $offset = 0): array {
        $db = Database::getInstance();
        $sql = "SELECT p.*, r.id as reg_display_id, u.email as user_email
                FROM payments p
                JOIN registrations r ON p.registration_id = r.id
                JOIN users u ON r.user_id = u.id";
        $where = [];
        $params = [];

        if (!empty($filters['search'])) {
            $searchTerm = '%' . $filters['search'] . '%';
            // Search by payment ID, registration ID, or user email
            $where[] = "(CAST(p.id AS CHAR) LIKE :search OR CAST(r.id AS CHAR) LIKE :search OR u.email LIKE :search)";
            $params['search'] = $searchTerm;
        }
        if (isset($filters['status']) && $filters['status'] !== '') {
             if ($filters['status'] === 'pending') {
                 $where[] = "(p.confirmed = 0 AND p.rejection_reason IS NULL)";
             } elseif ($filters['status'] === 'approved') {
                 $where[] = "p.confirmed = 1";
             } elseif ($filters['status'] === 'rejected') {
                  $where[] = "p.rejection_reason IS NOT NULL";
             }
        }
        // Add date range filters on p.created_at if needed

        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        $sql .= " ORDER BY p.created_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $db->prepare($sql);

        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        foreach ($params as $key => &$val) {
            $stmt->bindParam(":$key", $val);
        }
        unset($val);

        $stmt->execute();
        $paymentsData = $stmt->fetchAll();
        $payments = [];
        foreach ($paymentsData as $data) {
            $payments[] = self::hydrate($data);
        }
        return $payments;
    }

    /**
     * Count all payments (for admin) with filtering.
     */
    public static function countAllAdmin(array $filters = []): int {
        $db = Database::getInstance();
         $sql = "SELECT COUNT(p.id)
                FROM payments p
                JOIN registrations r ON p.registration_id = r.id
                JOIN users u ON r.user_id = u.id";
        $where = [];
        $params = [];

         if (!empty($filters['search'])) {
             $searchTerm = '%' . $filters['search'] . '%';
             $where[] = "(CAST(p.id AS CHAR) LIKE :search OR CAST(r.id AS CHAR) LIKE :search OR u.email LIKE :search)";
             $params['search'] = $searchTerm;
         }
         if (isset($filters['status']) && $filters['status'] !== '') {
             if ($filters['status'] === 'pending') $where[] = "(p.confirmed = 0 AND p.rejection_reason IS NULL)";
             elseif ($filters['status'] === 'approved') $where[] = "p.confirmed = 1";
             elseif ($filters['status'] === 'rejected') $where[] = "p.rejection_reason IS NOT NULL";
         }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /**
      * Get payments for a specific user with pagination.
      */
    public static function findByUserId(int $userId, int $limit = 10, int $offset = 0): array {
        $db = Database::getInstance();
        $sql = "SELECT p.*, r.id as reg_display_id
                FROM payments p
                JOIN registrations r ON p.registration_id = r.id
                WHERE r.user_id = :user_id
                ORDER BY p.created_at DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $db->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $paymentsData = $stmt->fetchAll();
        $payments = [];
        foreach ($paymentsData as $data) {
            $payments[] = self::hydrate($data);
        }
        return $payments;
    }

     /**
      * Count payments for a specific user.
      */
     public static function countByUserId(int $userId): int {
         $db = Database::getInstance();
         $sql = "SELECT COUNT(p.id)
                 FROM payments p
                 JOIN registrations r ON p.registration_id = r.id
                 WHERE r.user_id = :user_id";
         $stmt = $db->prepare($sql);
         $stmt->execute(['user_id' => $userId]);
         return (int) $stmt->fetchColumn();
     }


    /**
     * Create a new payment record.
     * @return bool|int Returns the new ID on success, false on failure.
     */
    public function create(): bool|int {
        $db = Database::getInstance();
        $sql = "INSERT INTO payments (registration_id, payment_image, issue_invoice, invoice_info_snapshot, confirmed, created_at, updated_at)
                VALUES (:registration_id, :payment_image, :issue_invoice, :invoice_info_snapshot, 0, NOW(), NOW())";
        $stmt = $db->prepare($sql);

        $success = $stmt->execute([
            'registration_id' => $this->registration_id,
            'payment_image' => $this->payment_image,
            'issue_invoice' => (int)$this->issue_invoice, // Store bool as 0 or 1
            'invoice_info_snapshot' => $this->invoice_info_snapshot
        ]);

        if ($success) {
            $this->id = (int)$db->lastInsertId();
            return $this->id;
        }
        return false;
    }

    /**
     * Update payment status to confirmed.
     */
    public function confirm(int $adminId): bool {
        if (!$this->id || $this->confirmed) return false;
        $db = Database::getInstance();
        $sql = "UPDATE payments SET
                   confirmed = 1,
                   confirmed_at = NOW(),
                   rejection_reason = NULL,
                   confirmed_by_admin_id = :admin_id,
                   updated_at = NOW()
                WHERE id = :id";
        $stmt = $db->prepare($sql);
        $success = $stmt->execute(['admin_id' => $adminId, 'id' => $this->id]);
        if ($success) {
            $this->confirmed = true;
            $this->confirmed_at = date('Y-m-d H:i:s'); // Approximate
            $this->rejection_reason = null;
            $this->confirmed_by_admin_id = $adminId;
        }
        return $success;
    }

    /**
     * Update payment status to rejected.
     */
    public function reject(int $adminId, string $reason): bool {
        if (!$this->id || $this->confirmed) return false; // Can't reject if already confirmed
        $db = Database::getInstance();
         $sql = "UPDATE payments SET
                   confirmed = 0,
                   confirmed_at = NOW(), -- Timestamp of rejection action
                   rejection_reason = :reason,
                   confirmed_by_admin_id = :admin_id,
                   updated_at = NOW()
                WHERE id = :id";
        $stmt = $db->prepare($sql);
        $success = $stmt->execute([
            'reason' => $reason,
            'admin_id' => $adminId,
            'id' => $this->id
        ]);
        if ($success) {
            $this->confirmed = false;
             $this->confirmed_at = date('Y-m-d H:i:s'); // Approximate time of rejection
            $this->rejection_reason = $reason;
            $this->confirmed_by_admin_id = $adminId;
        }
        return $success;
    }

    /**
     * Create Payment object from database data.
     */
    private static function hydrate(array $data): self {
        $payment = new self();
        $payment->id = (int)$data['id'];
        $payment->registration_id = (int)$data['registration_id'];
        $payment->payment_image = $data['payment_image'];
        $payment->issue_invoice = (bool)$data['issue_invoice'];
        $payment->invoice_info_snapshot = $data['invoice_info_snapshot'];
        $payment->confirmed = (bool)$data['confirmed'];
        $payment->confirmed_at = $data['confirmed_at'];
        $payment->rejection_reason = $data['rejection_reason'];
        $payment->confirmed_by_admin_id = isset($data['confirmed_by_admin_id']) ? (int)$data['confirmed_by_admin_id'] : null;
        $payment->created_at = $data['created_at'];
        $payment->updated_at = $data['updated_at'];

        // Optionally hydrate related registration ID/User Email from join
         if (isset($data['reg_display_id'])) {
             $payment->registration = new Registration();
             $payment->registration->id = (int)$data['reg_display_id'];
             if(isset($data['user_email'])) {
                 $payment->registration->user = new User();
                 $payment->registration->user->email = $data['user_email'];
             }
         }


        return $payment;
    }
}