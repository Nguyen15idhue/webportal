<?php

// Basic Configuration
define('BASE_URL', 'http://localhost/webportal/public'); // IMPORTANT: Set to your actual base URL
define('APP_NAME', 'Web Portal Đo Đạc');

// Database Configuration (Example for MySQL)
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'webportal_db'); // Replace with your DB name
define('DB_USER', 'root');         // Replace with your DB user
define('DB_PASS', '');             // Replace with your DB password
define('DB_CHARSET', 'utf8mb4');

// Session Configuration
define('SESSION_NAME', 'webportal_session');

// Error Reporting (Development vs Production)
// For development:
error_reporting(E_ALL);
ini_set('display_errors', 1);
// For production:
// error_reporting(0);
// ini_set('display_errors', 0);
// ini_set('log_errors', 1);
// ini_set('error_log', '/path/to/your/php-error.log'); // Set a path

// --- Permissions (Example - Can be loaded from DB later) ---
// This structure mirrors the JS example but should ideally be managed server-side
$rolePermissions = [
    'SuperAdmin' => [
        'dashboard' => true, 'user_management' => true, 'user_create' => true, 'user_edit' => true, 'user_status_toggle' => true,
        'account_management' => true, 'account_create' => true, 'account_edit' => true, 'account_status_toggle' => true, 'account_delete' => true,
        'invoice_management' => true, 'transaction_approve' => true, 'transaction_reject' => true,
        'referral_management' => true, 'referral_settings_view' => true, 'referral_settings_edit' => true, 'referral_payout' => true,
        'reports' => true, 'permission_management' => true, 'permission_edit' => true, 'admin_user_create' => true,
        'settings' => true,
    ],
    'Admin' => [
        'dashboard' => true, 'user_management' => true, 'user_create' => true, 'user_edit' => true, 'user_status_toggle' => true,
        'account_management' => true, 'account_create' => true, 'account_edit' => true, 'account_status_toggle' => true, 'account_delete' => false,
        'invoice_management' => true, 'transaction_approve' => true, 'transaction_reject' => true,
        'referral_management' => true, 'referral_settings_view' => false, 'referral_settings_edit' => false, 'referral_payout' => true,
        'reports' => true, 'permission_management' => false, 'permission_edit' => false, 'admin_user_create' => false,
        'settings' => true,
    ],
    'Operator' => [
        'dashboard' => true, 'user_management' => true, 'user_create' => false, 'user_edit' => false, 'user_status_toggle' => false,
        'account_management' => true, 'account_create' => false, 'account_edit' => false, 'account_status_toggle' => false, 'account_delete' => false,
        'invoice_management' => true, 'transaction_approve' => false, 'transaction_reject' => false,
        'referral_management' => true, 'referral_settings_view' => false, 'referral_settings_edit' => false, 'referral_payout' => false,
        'reports' => true, 'permission_management' => false, 'permission_edit' => false, 'admin_user_create' => false,
        'settings' => true,
    ],
    'User' => [ // Basic permissions for logged-in customer
        'user_dashboard' => true, 'user_map' => true, 'user_order' => true, 'user_accounts' => true,
        'user_transactions' => true, 'user_referral' => true, 'user_info' => true, 'user_settings' => true,
    ]
];

// Define roles constants for easier checks
define('ROLE_SUPER_ADMIN', 'SuperAdmin');
define('ROLE_ADMIN', 'Admin');
define('ROLE_OPERATOR', 'Operator');
define('ROLE_USER', 'User'); // Customer role

define('ROLES_ADMIN', [ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_OPERATOR]);

// Upload Directory
define('UPLOADS_DIR', __DIR__ . '/../public/uploads'); // Adjust path as needed
define('UPLOADS_URL', BASE_URL . '/uploads');