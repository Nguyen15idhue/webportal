<?php

namespace App\Core;

use App\Models\User;
use App\Models\AdminUser;

class Auth {
    // Start session if not already started
    public static function startSession(): void {
        if (session_status() == PHP_SESSION_NONE) {
            session_name(SESSION_NAME);
            session_start();
        }
    }

    // Login a regular user
    public static function loginUser(User $user): void {
        self::startSession();
        session_regenerate_id(true); // Prevent session fixation
        $_SESSION['user_id'] = $user->id;
        $_SESSION['user_role'] = ROLE_USER; // Set default user role
        // Store other relevant user data if needed, but avoid storing sensitive info
        $_SESSION['user_fullname'] = $user->fullname;
        $_SESSION['user_email'] = $user->email;
    }

    // Login an admin user
    public static function loginAdmin(AdminUser $adminUser): void {
        self::startSession();
        session_regenerate_id(true);
        $_SESSION['admin_user_id'] = $adminUser->id;
        $_SESSION['admin_user_role'] = $adminUser->role; // Role from DB
        // Store other relevant admin user data
        $_SESSION['admin_user_name'] = $adminUser->name;
        $_SESSION['admin_user_email'] = $adminUser->email;
    }

    // Logout the current user/admin
    public static function logout(): void {
        self::startSession();
        $_SESSION = []; // Unset all session variables
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }

    // Check if a regular user is logged in
    public static function checkUser(): bool {
        self::startSession();
        return isset($_SESSION['user_id']);
    }

     // Check if an admin user is logged in
    public static function checkAdmin(): bool {
        self::startSession();
        return isset($_SESSION['admin_user_id']) && in_array(self::adminRole(), ROLES_ADMIN);
    }

     // Check if *any* user (admin or regular) is logged in
    public static function check(): bool {
        return self::checkUser() || self::checkAdmin();
    }

    // Check if the visitor is a guest (not logged in)
    public static function guest(): bool {
        return !self::check();
    }

    // Get the logged-in regular user's ID
    public static function userId(): ?int {
        self::startSession();
        return $_SESSION['user_id'] ?? null;
    }

    // Get the logged-in admin user's ID
    public static function adminUserId(): ?int {
        self::startSession();
        return $_SESSION['admin_user_id'] ?? null;
    }

    // Get the logged-in regular user object (fetches from DB)
    public static function user(): ?User {
        if (!self::checkUser()) {
            return null;
        }
        // TODO: Implement caching if needed
        return User::findById(self::userId());
    }

    // Get the logged-in admin user object (fetches from DB)
    public static function adminUser(): ?AdminUser {
        if (!self::checkAdmin()) {
            return null;
        }
        // TODO: Implement caching if needed
        return AdminUser::findById(self::adminUserId());
    }

    // Get the logged-in admin user's role
    public static function adminRole(): ?string {
        self::startSession();
        return $_SESSION['admin_user_role'] ?? null;
    }

    // Check if the logged-in user/admin has a specific permission
    public static function hasPermission(string $permissionKey): bool {
        self::startSession();
        global $rolePermissions; // Use the global permissions array from config.php

        $role = null;
        if (self::checkAdmin()) {
            $role = self::adminRole();
        } elseif (self::checkUser()) {
            $role = ROLE_USER; // Assuming a default 'User' role for customers
        }

        if (!$role) {
            return false; // No user logged in
        }

        // SuperAdmin always has permission
        if ($role === ROLE_SUPER_ADMIN) {
            return true;
        }

        return isset($rolePermissions[$role]) && !empty($rolePermissions[$role][$permissionKey]);
    }

    // Get specific data from session
    public static function session(string $key, $default = null) {
        self::startSession();
        return $_SESSION[$key] ?? $default;
    }

    // Flash message handling (simple example)
    public static function setFlash(string $key, $value): void {
        self::startSession();
        $_SESSION['_flash'][$key] = $value;
    }

    public static function getFlash(string $key, $default = null) {
        self::startSession();
        $value = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);
        return $value;
    }

     // Redirect if not authenticated as user
    public static function authenticateUser(): void {
        if (!self::checkUser()) {
            self::setFlash('error', 'Vui lòng đăng nhập.');
            redirect('login');
        }
    }

    // Redirect if not authenticated as admin
    public static function authenticateAdmin(?string $requiredRole = null): void {
        if (!self::checkAdmin()) {
            self::setFlash('error', 'Vui lòng đăng nhập với tư cách quản trị viên.');
            redirect('admin/login'); // Assuming an admin login route
        }
        if ($requiredRole && self::adminRole() !== $requiredRole && self::adminRole() !== ROLE_SUPER_ADMIN) {
             // If a specific role is required and the user doesn't have it (and isn't SuperAdmin)
             self::setFlash('error', 'Bạn không có quyền truy cập mục này.');
             redirect('admin/dashboard'); // Or redirect back
        }
    }

    // Redirect if user doesn't have permission
    public static function authorize(string $permission): void {
        if (!self::hasPermission($permission)) {
            self::setFlash('error', 'Bạn không có quyền thực hiện hành động này.');
            // Redirect intelligently based on user type
            if (self::checkAdmin()) {
                redirect('admin/dashboard');
            } elseif (self::checkUser()) {
                redirect('dashboard');
            } else {
                redirect('login');
            }
        }
    }
}