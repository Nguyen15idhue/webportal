<?php

namespace App\Models;

use PDO;

class User {
    public int $id;
    public string $fullname;
    public string $email;
    public ?string $phone;
    public string $password; // Hashed password
    public ?string $company_name;
    public ?string $tax_code;
    public ?string $company_address;
    public ?string $invoice_email;
    public ?string $collaborator_code; // User's own code if they are a referrer
    public ?string $referred_by_collaborator_code;
    public ?string $user_bank_name;
    public ?string $user_bank_account;
    public ?string $user_bank_owner;
    public ?string $user_bank_branch;
    public ?string $user_momo;
    public string $status; // e.g., 'active', 'inactive'
    public string $created_at;
    public string $updated_at;

    // Find user by email
    public static function findByEmail(string $email): ?self {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        $userData = $stmt->fetch();

        if ($userData) {
            return self::hydrate($userData);
        }
        return null;
    }

    // Find user by ID
    public static function findById(int $id): ?self {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $userData = $stmt->fetch();

        if ($userData) {
            return self::hydrate($userData);
        }
        return null;
    }

     // Get all users (for admin, add pagination/filtering)
    public static function getAll(int $limit = 20, int $offset = 0): array {
        $db = Database::getInstance();
        // TODO: Add filtering/searching capabilities
        $stmt = $db->prepare("SELECT * FROM users ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $usersData = $stmt->fetchAll();
        $users = [];
        foreach ($usersData as $data) {
            $users[] = self::hydrate($data);
        }
        return $users;
    }

     // Count total users (for pagination)
    public static function countAll(): int {
        $db = Database::getInstance();
        // TODO: Add filtering
        $stmt = $db->query("SELECT COUNT(*) FROM users");
        return (int) $stmt->fetchColumn();
    }

    // Create a new user
    public function create(): bool {
        $db = Database::getInstance();
        $sql = "INSERT INTO users (fullname, email, password, phone, company_name, tax_code, company_address, invoice_email, collaborator_code, referred_by_collaborator_code, user_bank_name, user_bank_account, user_bank_owner, user_bank_branch, user_momo, status, created_at, updated_at)
                VALUES (:fullname, :email, :password, :phone, :company_name, :tax_code, :company_address, :invoice_email, :collaborator_code, :referred_by_collaborator_code, :user_bank_name, :user_bank_account, :user_bank_owner, :user_bank_branch, :user_momo, :status, NOW(), NOW())";
        $stmt = $db->prepare($sql);

        // Hash password before saving
        $hashedPassword = password_hash($this->password, PASSWORD_DEFAULT);

        return $stmt->execute([
            'fullname' => $this->fullname,
            'email' => $this->email,
            'password' => $hashedPassword,
            'phone' => $this->phone,
            'company_name' => $this->company_name,
            'tax_code' => $this->tax_code,
            'company_address' => $this->company_address,
            'invoice_email' => $this->invoice_email,
            'collaborator_code' => $this->collaborator_code,
            'referred_by_collaborator_code' => $this->referred_by_collaborator_code,
            'user_bank_name' => $this->user_bank_name,
            'user_bank_account' => $this->user_bank_account,
            'user_bank_owner' => $this->user_bank_owner,
            'user_bank_branch' => $this->user_bank_branch,
            'user_momo' => $this->user_momo,
            'status' => $this->status ?? 'active' // Default status
        ]);
    }

    // Update user data
    public function update(): bool {
         $db = Database::getInstance();
         // Build query dynamically based on what needs updating
         $fields = [
             'fullname' => $this->fullname,
             'phone' => $this->phone,
             'company_name' => $this->company_name,
             'tax_code' => $this->tax_code,
             'company_address' => $this->company_address,
             'invoice_email' => $this->invoice_email,
             'user_bank_name' => $this->user_bank_name,
             'user_bank_account' => $this->user_bank_account,
             'user_bank_owner' => $this->user_bank_owner,
             'user_bank_branch' => $this->user_bank_branch,
             'user_momo' => $this->user_momo,
             'status' => $this->status,
             // Add other updatable fields
         ];
         // Only update password if a new one is provided
         if (!empty($this->password)) {
             $fields['password'] = password_hash($this->password, PASSWORD_DEFAULT);
         }

         $setParts = [];
         $params = ['id' => $this->id];
         foreach ($fields as $key => $value) {
             if ($value !== null) { // Allow updating to empty string but not null unless intended
                 $setParts[] = "`$key` = :$key";
                 $params[$key] = $value;
             }
         }
         $setClause = implode(', ', $setParts);

         if (empty($setClause)) return true; // Nothing to update

         $sql = "UPDATE users SET " . $setClause . ", updated_at = NOW() WHERE id = :id";
         $stmt = $db->prepare($sql);
         return $stmt->execute($params);
    }

    // Delete a user (consider soft delete)
    public static function delete(int $id): bool {
        $db = Database::getInstance();
        // Consider soft deletes: UPDATE users SET status = 'deleted', deleted_at = NOW() WHERE id = :id
        $stmt = $db->prepare("DELETE FROM users WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    // Helper to create User object from DB data
    private static function hydrate(array $data): self {
        $user = new self();
        $user->id = (int)$data['id'];
        $user->fullname = $data['fullname'];
        $user->email = $data['email'];
        $user->password = $data['password']; // Keep hashed password
        $user->phone = $data['phone'];
        $user->company_name = $data['company_name'];
        $user->tax_code = $data['tax_code'];
        $user->company_address = $data['company_address'];
        $user->invoice_email = $data['invoice_email'];
        $user->collaborator_code = $data['collaborator_code'];
        $user->referred_by_collaborator_code = $data['referred_by_collaborator_code'];
        $user->user_bank_name = $data['user_bank_name'];
        $user->user_bank_account = $data['user_bank_account'];
        $user->user_bank_owner = $data['user_bank_owner'];
        $user->user_bank_branch = $data['user_bank_branch'];
        $user->user_momo = $data['user_momo'];
        $user->status = $data['status'];
        $user->created_at = $data['created_at'];
        $user->updated_at = $data['updated_at'];
        return $user;
    }
}