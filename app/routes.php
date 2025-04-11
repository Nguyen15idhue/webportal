<?php

// Define routes here using the $router instance from public/index.php

// Guest Routes (Login/Register)
$router->get('login', 'AuthController@showLoginForm');
$router->post('login', 'AuthController@login');
$router->get('register', 'AuthController@showRegisterForm');
$router->post('register', 'AuthController@register');
$router->post('logout', 'AuthController@logout'); // Use POST for logout

// Admin Login Routes (Optional - Separate or same form with role check)
// $router->get('admin/login', 'AuthController@showAdminLoginForm');
// $router->post('admin/login', 'AuthController@adminLogin');

// --- User Routes (Require User Authentication) ---
$router->get('/', 'User\DashboardController@index'); // Default to user dashboard if logged in
$router->get('dashboard', 'User\DashboardController@index');
$router->get('map', 'User\MapController@index');

// Order Flow
$router->get('order/packages', 'User\OrderController@showPackages');
$router->post('order/select-package', 'User\OrderController@selectPackage'); // Or handle via JS linking to details form
$router->get('order/details', 'User\OrderController@showDetailsForm'); // Might need package ID
$router->post('order/process-details', 'User\OrderController@processDetails');
$router->get('order/payment', 'User\OrderController@showPayment'); // Needs registration ID
$router->post('order/submit-proof', 'User\OrderController@submitProof'); // Needs registration ID

// User Account & Transaction Management
$router->get('accounts', 'User\AccountController@index');
$router->get('transactions', 'User\TransactionController@index');
$router->get('referrals', 'User\ReferralController@index');

// Info & Support
$router->get('guide', 'User\InfoController@showGuide');
$router->get('support', 'User\InfoController@showSupport');

// Settings
$router->get('settings/profile', 'User\SettingController@showProfile');
$router->post('settings/profile', 'User\SettingController@updateProfile');
$router->post('settings/password', 'User\SettingController@changePassword');
$router->get('settings/payment-method', 'User\SettingController@showPaymentMethod');
$router->post('settings/payment-method', 'User\SettingController@savePaymentMethod');
$router->get('settings/invoice-info', 'User\SettingController@showInvoiceInfo');
$router->post('settings/invoice-info', 'User\SettingController@saveInvoiceInfo');


// --- Admin Routes (Require Admin Authentication & Permissions) ---
$router->get('admin/dashboard', 'Admin\DashboardController@index');

// User Management (Customers)
$router->get('admin/users', 'Admin\UserController@index');
$router->get('admin/users/create', 'Admin\UserController@create');
$router->post('admin/users', 'Admin\UserController@store');
$router->get('admin/users/{id}/edit', 'Admin\UserController@edit');
$router->post('admin/users/{id}', 'Admin\UserController@update'); // Could use PUT/PATCH with method spoofing
$router->post('admin/users/{id}/toggle-status', 'Admin\UserController@toggleStatus'); // API-like endpoint
// Add routes for view/delete if needed

// Measurement Account Management
$router->get('admin/accounts', 'Admin\AccountController@index');
$router->get('admin/accounts/create', 'Admin\AccountController@create');
$router->post('admin/accounts', 'Admin\AccountController@store');
$router->get('admin/accounts/{id}/edit', 'Admin\AccountController@edit');
$router->post('admin/accounts/{id}', 'Admin\AccountController@update');
// Add routes for suspend, activate, renew, delete actions (likely POST)

// Transaction Management
$router->get('admin/transactions', 'Admin\TransactionController@index');
$router->post('admin/transactions/{id}/approve', 'Admin\TransactionController@approve');
$router->post('admin/transactions/{id}/reject', 'Admin\TransactionController@reject');
// Add routes for view details/proof

// Referral Management
$router->get('admin/referrals', 'Admin\ReferralController@index'); // Shows NGT list
$router->get('admin/referrals/list', 'Admin\ReferralController@listReferrals'); // Shows lượt GT list
$router->get('admin/referrals/settings', 'Admin\ReferralController@showSettings');
$router->post('admin/referrals/settings', 'Admin\ReferralController@saveSettings');
$router->post('admin/referrals/{code}/payout', 'Admin\ReferralController@processPayout');
// Add routes for viewing details

// Reports
$router->get('admin/reports', 'Admin\ReportController@index');
$router->post('admin/reports/generate', 'Admin\ReportController@generate'); // For filtering

// Permissions & Admin User Management
$router->get('admin/permissions', 'Admin\PermissionController@index'); // Role/Permission overview
$router->post('admin/permissions/save', 'Admin\PermissionController@savePermissions'); // Save role permissions
$router->get('admin/permissions/admins/create', 'Admin\PermissionController@createAdminUser'); // Show form to add Admin/Operator
$router->post('admin/permissions/admins', 'Admin\PermissionController@storeAdminUser'); // Store new Admin/Operator
// Add routes for editing/deleting Admin/Operator users

// Admin Profile
$router->get('admin/profile', 'Admin\ProfileController@index');
$router->post('admin/profile', 'Admin\ProfileController@update');
$router->post('admin/profile/password', 'Admin\ProfileController@changePassword');

// --- API Routes (Example - For AJAX calls from JS) ---
// These should handle requests and return JSON
// $router->post('api/admin/users/{id}/toggle-status', 'Api\Admin\UserController@toggleStatus');
// $router->post('api/admin/transactions/{id}/approve', 'Api\Admin\TransactionController@approve');
// $router->post('api/admin/transactions/{id}/reject', 'Api\Admin\TransactionController@reject');
// ... etc ...