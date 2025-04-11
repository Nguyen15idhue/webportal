<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Models\SurveyAccount;
use App\Models\Registration;
use App\Models\Package; // Needed for filters
use PDOException;

class AccountController {

    public function __construct() {
        Auth::authenticateAdmin();
    }

    /**
     * Display a list of survey accounts.
     */
    public function index(): void {
        Auth::authorize('account_management');

        $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
        $search = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_SPECIAL_CHARS); // Search username, reg ID, user email
        $status_filter = filter_input(INPUT_GET, 'status'); // 'active', 'inactive', 'expired' etc.
        $package_id_filter = filter_input(INPUT_GET, 'package_id', FILTER_VALIDATE_INT);
        $limit = 15;
        $offset = ($page - 1) * $limit;

        $filters = [];
        if ($search) $filters['search'] = $search;
        if ($package_id_filter) $filters['package_id'] = $package_id_filter;

        // Handle status filtering logic (might need complex query in model)
        // Example: 'active', 'inactive' maps to SurveyAccount.active
        // 'expired' needs check on Registration.end_date and Registration.status
        if ($status_filter === 'active') {
            $filters['status'] = true; // Filter SurveyAccount.active = 1
            // Additionally might need to filter out expired registrations in model query
        } elseif ($status_filter === 'inactive') {
             $filters['status'] = false; // Filter SurveyAccount.active = 0
        } elseif ($status_filter === 'expired') {
            $filters['registration_status'] = 'expired'; // Need model to handle this filter
        }
        // Add other status filters (pending_confirmation, pending_payment) based on Registration status


        try {
            $accounts = SurveyAccount::getAllAdmin($filters, $limit, $offset);
            $totalAccounts = SurveyAccount::countAllAdmin($filters);
            $totalPages = ceil($totalAccounts / $limit);
            $packages = Package::getAllActive(); // For filter dropdown
        } catch (PDOException $e) {
             error_log("Survey Account List Error: " . $e->getMessage());
             Auth::setFlash('error', 'Lỗi khi tải danh sách tài khoản đo đạc.');
             $accounts = [];
             $totalAccounts = 0;
             $totalPages = 0;
             $packages = [];
        }

        view('admin.accounts.index', [
            'title' => 'Quản lý tài khoản đo đạc',
            'activeSection' => 'admin-account-management',
            'accounts' => $accounts,
            'packages' => $packages, // For filter dropdown
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalAccounts' => $totalAccounts,
            'filters' => [ // Pass filters back to view
                'search' => $search,
                'status' => $status_filter,
                'package_id' => $package_id_filter
            ]
        ], 'admin');
    }

    /**
     * Show the form to manually create a survey account.
     * Note: Usually accounts are created during transaction approval.
     * This manual creation might be for special cases.
     */
    public function create(): void {
        Auth::authorize('account_create');

        // Fetch necessary data for dropdowns (e.g., approved registrations without accounts yet)
        $registrations = Registration::getAllAdmin(['status' => 'active', 'needs_account' => true]); // Requires specific model method

        view('admin.accounts.form', [
            'title' => 'Tạo tài khoản đo đạc thủ công',
            'activeSection' => 'admin-account-management',
            'account' => null,
            'registrations' => $registrations, // Pass registrations to link
            'formAction' => url('admin/accounts'),
            'formMethod' => 'POST'
        ], 'admin');
    }

    /**
     * Store a manually created survey account.
     */
    public function store(): void {
        Auth::authorize('account_create');

         if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token()) {
             Auth::setFlash('error', 'Yêu cầu không hợp lệ.');
             redirect('admin/accounts');
         }

         // --- Basic Validation ---
         $registrationId = filter_input(INPUT_POST, 'registration_id', FILTER_VALIDATE_INT);
         $username = filter_input(INPUT_POST, 'username_acc', FILTER_SANITIZE_SPECIAL_CHARS);
         $password = $_POST['password_acc'] ?? ''; // Get raw password
         // Caster info usually derived from registration/station
         // $isActive = isset($_POST['active']);

         if (empty($registrationId) || empty($username) || empty($password)) {
             Auth::setFlash('error', 'Vui lòng điền Đăng ký liên kết, Username, Password.');
             redirect('admin/accounts/create');
             return;
         }

         try {
             // Fetch Registration and assigned Station to get Caster info
             $registration = Registration::findById($registrationId);
             if (!$registration || $registration->status !== 'active') {
                 Auth::setFlash('error', 'Đăng ký không hợp lệ hoặc chưa được duyệt.');
                 redirect('admin/accounts/create');
                 return;
             }
             // Fetch station (requires Station model)
             // $station = Station::findById($registration->station_id);
             // if (!$station) { ... handle error ... }

             // Create Survey Account object
             $account = new SurveyAccount();
             $account->registration_id = $registrationId;
             $account->username_acc = $username;
             $account->password_acc = $password; // Consider hashing if needed
             // $account->caster_ip = $station->ip;
             // $account->caster_port = $station->port;
             // $account->mount_point = $station->mount_point; // Or generate based on username/station
             $account->active = true; // Manually created usually active

             if ($account->create()) {
                 Auth::setFlash('success', 'Đã tạo tài khoản đo đạc thủ công.');
                 redirect('admin/accounts');
             } else {
                 Auth::setFlash('error', 'Không thể tạo tài khoản.');
                 redirect('admin/accounts/create');
             }
         } catch (PDOException $e) {
             error_log("Survey Account Store Error: " . $e->getMessage());
             Auth::setFlash('error', 'Lỗi cơ sở dữ liệu khi tạo tài khoản.');
             redirect('admin/accounts/create');
         }
    }

    /**
     * Show the form for editing a survey account (e.g., change password, status).
     */
    public function edit(int $id): void {
        Auth::authorize('account_edit');

        try {
            $account = SurveyAccount::findById($id);
            if (!$account) {
                 Auth::setFlash('error', 'Không tìm thấy tài khoản đo đạc.');
                 redirect('admin/accounts');
                 return;
            }

            view('admin.accounts.form', [
                'title' => 'Sửa tài khoản đo đạc',
                'activeSection' => 'admin-account-management',
                'account' => $account,
                'registrations' => [], // Not needed for edit usually
                'formAction' => url('admin/accounts/' . $id),
                'formMethod' => 'POST' // Use POST with method spoofing if desired
            ], 'admin');

        } catch (PDOException $e) {
             error_log("Survey Account Edit Error: " . $e->getMessage());
             Auth::setFlash('error', 'Lỗi khi tải thông tin tài khoản.');
             redirect('admin/accounts');
        }
    }

    /**
     * Update the specified survey account.
     */
    public function update(int $id): void {
        Auth::authorize('account_edit');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token()) {
            Auth::setFlash('error', 'Yêu cầu không hợp lệ.');
            redirect('admin/accounts');
        }

        try {
            $account = SurveyAccount::findById($id);
             if (!$account) {
                 Auth::setFlash('error', 'Không tìm thấy tài khoản đo đạc.');
                 redirect('admin/accounts');
                 return;
             }

             // --- Basic Validation ---
             $newPassword = $_POST['password_acc'] ?? '';
             $isActive = isset($_POST['active']); // Checkbox value

             if (!empty($newPassword) && strlen($newPassword) < 5) { // Example validation
                  Auth::setFlash('error', 'Mật khẩu mới phải ít nhất 5 ký tự.');
                  redirect('admin/accounts/' . $id . '/edit');
                  return;
             }

            // Update properties
            if (!empty($newPassword)) {
                $account->password_acc = $newPassword; // Consider hashing if stored hashed
            }
            $account->active = $isActive;

            if ($account->update()) { // Update method should handle password/active
                Auth::setFlash('success', 'Đã cập nhật tài khoản đo đạc.');
                redirect('admin/accounts');
            } else {
                Auth::setFlash('error', 'Không thể cập nhật tài khoản.');
                redirect('admin/accounts/' . $id . '/edit');
            }

        } catch (PDOException $e) {
            error_log("Survey Account Update Error: " . $e->getMessage());
            Auth::setFlash('error', 'Lỗi cơ sở dữ liệu khi cập nhật tài khoản.');
            redirect('admin/accounts/' . $id . '/edit');
        }
    }

    /**
     * Toggle survey account status (active/inactive).
     * Use POST method. This version redirects.
     */
    public function toggleStatus(int $id): void {
        Auth::authorize('account_status_toggle');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token()) {
            Auth::setFlash('error', 'Yêu cầu không hợp lệ.');
            redirect('admin/accounts');
            return;
        }

        try {
            $account = SurveyAccount::findById($id);
            if (!$account) {
                 Auth::setFlash('error', 'Không tìm thấy tài khoản đo đạc.');
                 redirect('admin/accounts');
                 return;
             }

            // Toggle status
            $account->active = !$account->active;

            if ($account->update()) { // Use the update method
                 Auth::setFlash('success', 'Đã cập nhật trạng thái tài khoản.');
             } else {
                 Auth::setFlash('error', 'Không thể cập nhật trạng thái tài khoản.');
             }

        } catch (PDOException $e) {
             error_log("Survey Account Toggle Status Error: " . $e->getMessage());
             Auth::setFlash('error', 'Lỗi cơ sở dữ liệu khi cập nhật trạng thái.');
        }

        redirect('admin/accounts');
    }


    /**
     * Delete a survey account (use with caution).
     * Use POST method.
     */
    public function delete(int $id): void {
         Auth::authorize('account_delete');

         if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token()) {
             Auth::setFlash('error', 'Yêu cầu không hợp lệ.');
             redirect('admin/accounts');
             return;
         }

         // Optional: Add confirmation step if needed (e.g., separate confirmation page or JS confirm)

         try {
             $account = SurveyAccount::findById($id); // Check if exists before deleting
              if (!$account) {
                  Auth::setFlash('error', 'Không tìm thấy tài khoản để xóa.');
              } elseif (SurveyAccount::delete($id)) { // Static delete method
                 Auth::setFlash('success', 'Đã xóa tài khoản đo đạc.');
             } else {
                 Auth::setFlash('error', 'Không thể xóa tài khoản.');
             }
         } catch (PDOException $e) {
              error_log("Survey Account Delete Error: " . $e->getMessage());
              Auth::setFlash('error', 'Lỗi cơ sở dữ liệu khi xóa tài khoản.');
         }

         redirect('admin/accounts');
     }

}