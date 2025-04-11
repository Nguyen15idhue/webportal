<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Models\User;

class UserController {

    public function __construct() {
        Auth::authenticateAdmin(); // Ensure admin access for all user actions
    }

    /**
     * Display a list of registered users (customers).
     */
    public function index(): void {
        Auth::authorize('user_management'); // Check permission

        // Pagination parameters
        $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
        $limit = 20; // Users per page
        $offset = ($page - 1) * $limit;

        // Filtering parameters
        $filters = [
            'search' => filter_input(INPUT_GET, 'search', FILTER_SANITIZE_SPECIAL_CHARS) ?: null,
            'status' => filter_input(INPUT_GET, 'status', FILTER_SANITIZE_SPECIAL_CHARS) ?: null,
        ];
        // Remove empty filters
        $filters = array_filter($filters);

        // Fetch users and total count based on filters
        $users = User::getAll($filters, $limit, $offset);
        $totalUsers = User::countAll($filters);
        $totalPages = ceil($totalUsers / $limit);

        // Load the view
        view('admin.users.index', [
            'title' => 'Quản lý người dùng (KH)',
            'activeSection' => 'admin-user-management',
            'users' => $users,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalUsers' => $totalUsers,
            'limit' => $limit,
            'filters' => $filters // Pass filters back for display/form population
        ], 'admin');
    }

    /**
     * Show the form for creating a new user.
     * (Admin might not create users directly, maybe edit only?)
     * Adjust based on requirements.
     */
    public function create(): void {
        Auth::authorize('user_create');

        view('admin.users.form', [
            'title' => 'Thêm người dùng mới',
            'activeSection' => 'admin-user-management',
            'user' => new User(), // Pass empty user object
            'formAction' => url('admin/users'),
            'formMethod' => 'POST'
        ], 'admin');
    }

    /**
     * Store a newly created user in storage.
     * (Again, check if admin should do this)
     */
    public function store(): void {
        Auth::authorize('user_create');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token()) {
            Auth::setFlash('error', 'Yêu cầu không hợp lệ.');
            redirect('admin/users');
        }

        // --- Validation ---
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $fullname = filter_input(INPUT_POST, 'fullname', FILTER_SANITIZE_SPECIAL_CHARS);
        $password = $_POST['password'] ?? null; // Get raw password
        $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_SPECIAL_CHARS);
        // Add more fields as needed (phone, etc.)

        $errors = [];
        if (!$email) $errors['email'] = 'Email không hợp lệ.';
        if (empty($fullname)) $errors['fullname'] = 'Họ tên không được để trống.';
        if (empty($password) || strlen($password) < 6) $errors['password'] = 'Mật khẩu phải có ít nhất 6 ký tự.';
        if (!in_array($status, ['active', 'inactive'])) $status = 'active'; // Default or validation error

        // Check if email already exists
        if ($email && User::findByEmail($email)) {
            $errors['email'] = 'Email đã tồn tại.';
        }

        if (!empty($errors)) {
            Auth::setFlash('error', 'Vui lòng sửa các lỗi sau:');
             // Redirect back with errors and old input
             $_SESSION['_errors'] = $errors; // Implement error display in form view
             $_SESSION['_old_input'] = $_POST; // Implement old input helper
             redirect('admin/users/create');
        }
        // --- End Validation ---

        // Create user object
        $user = new User();
        $user->email = $email;
        $user->fullname = $fullname;
        $user->password = $password; // Model will hash this
        $user->status = $status;
        // Set other properties...

        if ($user->create()) {
            Auth::setFlash('success', 'Đã thêm người dùng thành công.');
            redirect('admin/users');
        } else {
            Auth::setFlash('error', 'Không thể thêm người dùng. Vui lòng thử lại.');
            $_SESSION['_old_input'] = $_POST;
            redirect('admin/users/create');
        }
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(int $id): void {
        Auth::authorize('user_edit');

        $user = User::findById($id);
        if (!$user) {
            Auth::setFlash('error', 'Không tìm thấy người dùng.');
            redirect('admin/users');
        }

        view('admin.users.form', [
            'title' => 'Sửa thông tin người dùng',
            'activeSection' => 'admin-user-management',
            'user' => $user, // Pass existing user object
            'formAction' => url('admin/users/' . $id),
            'formMethod' => 'POST' // Use POST with method spoofing if needed, or handle POST directly
        ], 'admin');
    }

    /**
     * Update the specified user in storage.
     */
    public function update(int $id): void {
        Auth::authorize('user_edit');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token()) {
            Auth::setFlash('error', 'Yêu cầu không hợp lệ.');
            redirect('admin/users');
        }

        $user = User::findById($id);
        if (!$user) {
            Auth::setFlash('error', 'Không tìm thấy người dùng.');
            redirect('admin/users');
        }

        // --- Validation ---
        $fullname = filter_input(INPUT_POST, 'fullname', FILTER_SANITIZE_SPECIAL_CHARS);
        $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_SPECIAL_CHARS);
        $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_SPECIAL_CHARS);
        $newPassword = $_POST['password'] ?? null; // Optional new password

        $errors = [];
        if (empty($fullname)) $errors['fullname'] = 'Họ tên không được để trống.';
        if (!in_array($status, ['active', 'inactive'])) $errors['status'] = 'Trạng thái không hợp lệ.';
        if (!empty($newPassword) && strlen($newPassword) < 6) $errors['password'] = 'Mật khẩu mới phải có ít nhất 6 ký tự.';
        // Add more validation as needed

        if (!empty($errors)) {
            Auth::setFlash('error', 'Vui lòng sửa các lỗi sau:');
             $_SESSION['_errors'] = $errors;
             $_SESSION['_old_input'] = $_POST;
             redirect('admin/users/' . $id . '/edit');
        }
        // --- End Validation ---

        // Update user object properties
        $user->fullname = $fullname;
        $user->status = $status;
        $user->phone = $phone ?: null; // Set null if empty
        if (!empty($newPassword)) {
            $user->password = $newPassword; // Model's update will hash this
        } else {
             $user->password = null; // Ensure plain password isn't kept if not changing
        }
        // Update other allowed fields...

        if ($user->update()) {
            Auth::setFlash('success', 'Đã cập nhật thông tin người dùng.');
            redirect('admin/users');
        } else {
            Auth::setFlash('error', 'Không thể cập nhật người dùng. Vui lòng thử lại.');
            $_SESSION['_old_input'] = $_POST;
            redirect('admin/users/' . $id . '/edit');
        }
    }

    /**
     * Toggle the status (active/inactive) of a user.
     * This is often better handled via an API endpoint returning JSON
     * for smoother UI updates with JavaScript.
     */
    public function toggleStatus(int $id): void {
         Auth::authorize('user_status_toggle');

         // This route expects POST for security
         if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token()) {
              // For API: return json_encode(['success' => false, 'message' => 'Invalid request.']);
              Auth::setFlash('error', 'Yêu cầu không hợp lệ.');
              redirect('admin/users');
              return; // Exit after redirect
         }


         $user = User::findById($id);
         if (!$user) {
             // For API: return json_encode(['success' => false, 'message' => 'User not found.']);
             Auth::setFlash('error', 'Không tìm thấy người dùng.');
             redirect('admin/users');
             return;
         }

         // Determine the new status
         $newStatus = ($user->status === 'active') ? 'inactive' : 'active';
         $actionText = ($newStatus === 'active') ? 'Mở khóa' : 'Khóa';

         // Update the status using the model method
         if ($user->updateStatus($newStatus)) {
              // For API: return json_encode(['success' => true, 'message' => "{$actionText} người dùng thành công.", 'newStatus' => $newStatus]);
             Auth::setFlash('success', "Đã {$actionText} người dùng '" . e($user->email) . "' thành công.");
         } else {
             // For API: return json_encode(['success' => false, 'message' => "Lỗi khi {$actionText} người dùng."]);
             Auth::setFlash('error', "Lỗi khi {$actionText} người dùng '" . e($user->email) . "'.");
         }

         redirect('admin/users'); // Redirect back to the list
    }

    /**
     * Remove the specified user from storage. (Use with caution!)
     */
    public function destroy(int $id): void {
        Auth::authorize('user_delete'); // Assuming a specific delete permission

         // Use POST for destructive actions, verify CSRF
         if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token()) {
             Auth::setFlash('error', 'Yêu cầu không hợp lệ.');
             redirect('admin/users');
         }


        if (User::delete($id)) { // Make sure delete method exists
            Auth::setFlash('success', 'Đã xóa người dùng.');
        } else {
            Auth::setFlash('error', 'Không thể xóa người dùng.');
        }
        redirect('admin/users');
    }
}