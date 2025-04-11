<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Admin - ' . APP_NAME) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> -->
    <script>
        // Tailwind config from admin HTML
        tailwind.config = { /* ... */ }
    </script>
    <style>
        /* --- CSS Tùy chỉnh và Tối ưu --- */
        /* Copy ALL custom styles from admin HTML <style> tag here */
        /* --- CSS Tùy chỉnh và Tối ưu --- */
        .content-section { display: none; } /* Sections initially hidden */
        .content-section.active { display: block; }

        /* --- Sidebar --- */
        .nav-item.active { /* ... */ }
        .nav-item { /* ... */ }
        .nav-item[disabled] { /* ... */ }
        .nav-item.hidden-by-permission { display: none !important; } /* Class to hide menu based on PHP */

        /* --- Inputs, Buttons, Tables --- */
        input[type="text"], input[type="email"], /* ... */ select { /* ... */ }
        input[type="file"] { /* ... */ }
        button, .btn { /* ... */ }
        .btn { /* ... */ }
        .btn-primary { /* ... */ } /* etc. for all buttons */
        .btn-icon { /* ... */ }
        button:disabled, .btn:disabled { /* ... */ }

        /* Tables */
        tbody tr:nth-child(odd) { /* ... */ }
        tbody tr:hover { /* ... */ }
        th, td { /* ... */ }
        thead th { /* ... */ }

        /* Badges */
        .badge { /* ... */ }
        .badge-green { /* ... */ } /* etc. for all badges */

        /* Modal Styling */
        .modal { /* ... */ }
        .modal.active { /* ... */ }
        .modal-content { /* ... */ }
        .modal-close { /* ... */ }
        .modal-close:hover, .modal-close:focus { /* ... */ }

        /* Charts Placeholder */
        .chart-container { /* ... */ }

        /* --- Responsive Sidebar specific styles --- */
        .sidebar { /* ... */ }
        .sidebar.open { /* ... */ }
        @media (min-width: 768px) { .sidebar { /* ... */ } }
        .sidebar-overlay { /* ... */ }
        .sidebar-overlay.active { /* ... */ }
        @media (min-width: 768px) { .sidebar-overlay { /* ... */ } }

         /* Toast Container Style */
         #toast-container {
            position: fixed; top: 20px; right: 20px; z-index: 1050; display: flex;
            flex-direction: column; align-items: flex-end; gap: 10px;
         }
         #toast-container .toast {
             padding: 10px 15px; border-radius: 6px; color: white; font-size: 14px;
             display: inline-flex; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.15);
             opacity: 1; transition: opacity 0.3s ease-out, transform 0.3s ease-out; max-width: 300px; word-break: break-word;
             transform: translateX(100%); /* Start off-screen */
         }
         #toast-container .toast.show {
            transform: translateX(0); /* Slide in */
         }
         #toast-container .toast-success { background-color: rgba(34, 197, 94, 0.95); }
         #toast-container .toast-error { background-color: rgba(239, 68, 68, 0.95); }
         #toast-container .toast-warning { background-color: rgba(245, 158, 11, 0.95); }
         #toast-container .toast-info { background-color: rgba(59, 130, 246, 0.95); }

         /* Add any other custom CSS */
    </style>
</head>
<body class="bg-gray-100 text-gray-800 text-sm md:text-xs">

    <!-- Overlay for mobile sidebar -->
    <div id="sidebar-overlay" class="sidebar-overlay" onclick="toggleSidebar()"></div>

    <div class="flex min-h-screen">

        <!-- Sidebar -->
        <div id="admin-sidebar" class="sidebar w-64 bg-white shadow-lg flex flex-col shrink-0 overflow-y-auto md:shadow-md">
            <!-- Logo/Title -->
            <div class="p-4 border-b border-gray-200 flex justify-between items-center">
                <h1 class="text-lg font-bold text-primary-700 flex items-center gap-2">
                    <i class="fas fa-user-shield"></i><span>Trang Quản Trị</span>
                </h1>
                <button class="md:hidden text-gray-500 hover:text-gray-700" onclick="toggleSidebar()">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Menu Area -->
            <div class="flex-grow p-4">
                <!-- Admin User Info -->
                <div class="flex items-center mb-6 p-2 rounded-lg bg-gray-50 border border-gray-200">
                    <div class="w-10 h-10 rounded-full bg-admin-500 flex items-center justify-center text-white shrink-0"><i class="fas fa-user-tie"></i></div>
                    <div class="ml-3 overflow-hidden">
                        <p id="admin-user-name" class="font-semibold text-sm text-gray-900 truncate"><?= e(App\Core\Auth::session('admin_user_name', 'Admin')) ?></p>
                        <p id="admin-user-role" class="text-xs text-gray-500"><?= e(App\Core\Auth::session('admin_user_role', 'Role')) ?></p>
                    </div>
                </div>

                <!-- Main Menu -->
                <nav class="space-y-1" id="admin-main-menu">
                    <?php if (can('dashboard')): ?>
                    <a href="<?= url('admin/dashboard') ?>" class="nav-item flex items-center p-3 rounded-lg text-gray-700 hover:bg-primary-50 cursor-pointer <?= ($activeSection ?? '') === 'admin-dashboard' ? 'active' : '' ?>" data-section="admin-dashboard">
                        <i class="fas fa-tachometer-alt w-5 h-5 mr-3 text-primary-600"></i> Dashboard
                    </a>
                    <?php endif; ?>
                    <?php if (can('user_management')): ?>
                    <a href="<?= url('admin/users') ?>" class="nav-item flex items-center p-3 rounded-lg text-gray-700 hover:bg-primary-50 cursor-pointer <?= ($activeSection ?? '') === 'admin-user-management' ? 'active' : '' ?>" data-section="admin-user-management">
                        <i class="fas fa-users w-5 h-5 mr-3 text-primary-600"></i> QL người dùng
                    </a>
                    <?php endif; ?>
                    <?php if (can('account_management')): ?>
                    <a href="<?= url('admin/accounts') ?>" class="nav-item flex items-center p-3 rounded-lg text-gray-700 hover:bg-primary-50 cursor-pointer <?= ($activeSection ?? '') === 'admin-account-management' ? 'active' : '' ?>" data-section="admin-account-management">
                        <i class="fas fa-ruler-combined w-5 h-5 mr-3 text-primary-600"></i> QL TK đo đạc
                    </a>
                     <?php endif; ?>
                    <?php if (can('invoice_management')): ?>
                    <a href="<?= url('admin/transactions') ?>" class="nav-item flex items-center p-3 rounded-lg text-gray-700 hover:bg-primary-50 cursor-pointer <?= ($activeSection ?? '') === 'admin-invoice-management' ? 'active' : '' ?>" data-section="admin-invoice-management">
                        <i class="fas fa-file-invoice-dollar w-5 h-5 mr-3 text-primary-600"></i> QL hóa đơn/GD
                    </a>
                     <?php endif; ?>
                    <?php if (can('referral_management')): ?>
                    <a href="<?= url('admin/referrals') ?>" class="nav-item flex items-center p-3 rounded-lg text-gray-700 hover:bg-primary-50 cursor-pointer <?= ($activeSection ?? '') === 'admin-referral-management' ? 'active' : '' ?>" data-section="admin-referral-management">
                        <i class="fas fa-network-wired w-5 h-5 mr-3 text-primary-600"></i> QL người giới thiệu
                    </a>
                     <?php endif; ?>
                    <?php if (can('reports')): ?>
                    <a href="<?= url('admin/reports') ?>" class="nav-item flex items-center p-3 rounded-lg text-gray-700 hover:bg-primary-50 cursor-pointer <?= ($activeSection ?? '') === 'admin-reports' ? 'active' : '' ?>" data-section="admin-reports">
                        <i class="fas fa-chart-line w-5 h-5 mr-3 text-primary-600"></i> Báo cáo
                    </a>
                     <?php endif; ?>
                    <?php if (can('permission_management')): ?>
                    <a href="<?= url('admin/permissions') ?>" class="nav-item flex items-center p-3 rounded-lg text-gray-700 hover:bg-primary-50 cursor-pointer <?= ($activeSection ?? '') === 'admin-permission-management' ? 'active' : '' ?>" data-section="admin-permission-management">
                        <i class="fas fa-user-lock w-5 h-5 mr-3 text-primary-600"></i> QL phân quyền
                    </a>
                     <?php endif; ?>

                    <!-- Settings Section -->
                     <?php if (can('settings')): ?>
                    <div class="pt-4 mt-4 border-t border-gray-200">
                        <p class="text-xs font-semibold text-gray-500 uppercase px-3 mb-2">Cài đặt</p>
                        <nav class="space-y-1">
                            <a href="<?= url('admin/profile') ?>" class="nav-item flex items-center p-3 rounded-lg text-gray-700 hover:bg-primary-50 cursor-pointer <?= ($activeSection ?? '') === 'admin-profile' ? 'active' : '' ?>" data-section="admin-profile">
                                <i class="fas fa-id-card w-5 h-5 mr-3 text-primary-600"></i> Thông tin tài khoản
                            </a>
                            <!-- Logout Form -->
                            <form action="<?= url('logout') ?>" method="POST" class="w-full">
                                <?= csrf_field() ?>
                                <button type="submit" class="nav-item flex items-center p-3 rounded-lg text-gray-700 hover:bg-red-50 cursor-pointer w-full text-left" onclick="return confirm('Bạn chắc chắn muốn đăng xuất?')">
                                    <i class="fas fa-sign-out-alt w-5 h-5 mr-3 text-red-600"></i> Đăng xuất
                                </button>
                            </form>
                        </nav>
                    </div>
                     <?php endif; ?>
                </nav>
            </div>
        </div>

        <!-- Main content -->
        <div id="main-content" class="flex-1 overflow-y-auto bg-gray-100 md:ml-0 transition-all duration-300 ease-in-out">
            <!-- Mobile Header -->
            <div class="sticky top-0 z-20 bg-white shadow-sm p-3 flex items-center justify-between md:hidden">
                <button id="hamburger-button" class="text-gray-600 hover:text-primary-600" onclick="toggleSidebar()">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <h2 id="current-page-title" class="text-sm font-semibold text-gray-700">
                   <?= e($pageTitle ?? $title ?? 'Admin Dashboard') ?>
                </h2>
                <div></div> <!-- Placeholder -->
            </div>

             <!-- Flash Messages -->
             <div class="p-3 sm:p-4 md:p-6 lg:p-8 pt-0 md:pt-6 lg:pt-8"> <!-- Adjust padding for flash -->
                <?php if ($flash_success = App\Core\Auth::getFlash('success')): ?>
                    <div class="alert alert-success mb-4" role="alert"><?= e($flash_success) ?></div>
                <?php endif; ?>
                <?php if ($flash_error = App\Core\Auth::getFlash('error')): ?>
                    <div class="alert alert-error mb-4" role="alert"><?= e($flash_error) ?></div>
                <?php endif; ?>
                 <?php if ($flash_info = App\Core\Auth::getFlash('info')): ?>
                    <div class="alert alert-info mb-4" role="alert"><?= e($flash_info) ?></div>
                <?php endif; ?>
            </div>

            <!-- Content Area -->
            <div class="p-3 sm:p-4 md:p-6 lg:p-8 pt-0">
                <?= $content ?> <!-- View content will be injected here -->
            </div>
        </div>

    </div>

    <!-- Modals (Copy relevant modals from admin HTML here) -->
    <!-- Example: View Proof Modal -->
    <div id="viewProofModal" class="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="closeModal('viewProofModal')">×</span>
            <h3 class="text-lg font-semibold mb-4">Minh chứng GD <span id="proof-modal-title" class="font-bold"></span></h3>
            <img id="proof-modal-image" src="" alt="Minh chứng thanh toán" class="max-w-full h-auto mx-auto border max-h-[70vh] object-contain">
            <p class="text-xs text-center mt-2 text-gray-500">Ảnh minh chứng.</p>
        </div>
    </div>
     <!-- Example: Reject Transaction Modal -->
    <div id="rejectTransactionModal" class="modal">
         <div class="modal-content">
             <span class="modal-close" onclick="closeModal('rejectTransactionModal')">×</span>
             <h3 class="text-lg font-semibold mb-4">Từ chối giao dịch <span id="reject-modal-title" class="font-bold"></span></h3>
             <!-- IMPORTANT: Use POST method and CSRF token -->
             <form id="reject-transaction-form" method="POST">
                  <?= csrf_field() ?>
                  <input type="hidden" name="_method" value="POST"> <!-- Or PATCH/PUT if using method spoofing -->
                  <input type="hidden" id="reject-transaction-id" name="transaction_id">
                  <label for="rejection-reason" class="block text-sm font-medium text-gray-700 mb-1">Lý do từ chối <span class="text-red-500">*</span></label>
                  <textarea id="rejection-reason" name="reason" rows="3" class="w-full text-sm" placeholder="Nhập lý do rõ ràng..." required></textarea>
                  <div class="mt-4 text-right space-x-2">
                      <button type="button" class="btn-secondary" onclick="closeModal('rejectTransactionModal')">Hủy</button>
                      <button type="submit" class="btn-danger" id="confirm-reject-btn">Xác nhận Từ chối</button>
                  </div>
             </form>
         </div>
    </div>
     <!-- Example: Create Admin User Modal -->
     <div id="createAdminUserModal" class="modal">
          <div class="modal-content">
             <span class="modal-close" onclick="closeModal('createAdminUserModal')">×</span>
             <h3 class="text-lg font-semibold mb-4">Tạo tài khoản QTV/Vận hành</h3>
             <form action="<?= url('admin/permissions/admins') ?>" method="POST" onsubmit="disableButton(this.querySelector('button[type=submit]'))">
                 <?= csrf_field() ?>
                 <div class="space-y-4">
                      <div>
                          <label for="new-admin-name" class="block text-sm font-medium text-gray-700 mb-1">Họ tên <span class="text-red-500">*</span></label>
                          <input type="text" id="new-admin-name" name="name" required class="text-sm">
                      </div>
                      <div>
                          <label for="new-admin-email" class="block text-sm font-medium text-gray-700 mb-1">Email <span class="text-red-500">*</span></label>
                          <input type="email" id="new-admin-email" name="email" required class="text-sm">
                      </div>
                      <div>
                          <label for="new-admin-password" class="block text-sm font-medium text-gray-700 mb-1">Mật khẩu <span class="text-red-500">*</span></label>
                          <input type="password" id="new-admin-password" name="password" required class="text-sm">
                          <p class="text-xs text-gray-500 mt-1">Tạm thời, cần đổi sau khi đăng nhập.</p>
                      </div>
                       <div>
                          <label for="new-admin-role" class="block text-sm font-medium text-gray-700 mb-1">Vai trò <span class="text-red-500">*</span></label>
                          <select id="new-admin-role" name="role" required class="text-sm">
                              <option value="" disabled selected>-- Chọn vai trò --</option>
                              <option value="<?= ROLE_ADMIN ?>">Quản trị viên (Admin)</option>
                              <option value="<?= ROLE_OPERATOR ?>">Vận hành (Operator)</option>
                          </select>
                      </div>
                  </div>
                  <div class="mt-6 text-right space-x-2">
                      <button type="button" class="btn-secondary" onclick="closeModal('createAdminUserModal')">Hủy</button>
                      <button type="submit" class="btn-primary" id="create-admin-btn">Tạo tài khoản</button>
                  </div>
             </form>
         </div>
     </div>
     <!-- ... Add other necessary modals -->


    <!-- Toast Notification Container -->
    <div id="toast-container"></div>

    <script>
        // --- Core JS State ---
        const BASE_URL = '<?= BASE_URL ?>'; // Pass base URL from PHP
        const csrfToken = '<?= csrf_token() ?>'; // Pass CSRF token for AJAX

        // --- GIẢ LẬP VAI TRÒ & QUYỀN (Should be replaced by PHP checks where possible) ---
        // PHP should handle hiding/disabling elements server-side when possible
        // JS permission checks are mainly for dynamic UI updates after initial load
        const currentUserRole = '<?= e(App\Core\Auth::adminRole()) ?>'; // Get role from PHP Auth
        const rolePermissions = <?= json_encode($rolePermissions ?? []) ?>; // Pass permissions from config/PHP

        function hasPermission(permissionKey) {
            if (currentUserRole === '<?= ROLE_SUPER_ADMIN ?>') return true;
            return rolePermissions[currentUserRole] && rolePermissions[currentUserRole][permissionKey];
        }

        // --- Sidebar Toggle ---
        const sidebar = document.getElementById('admin-sidebar');
        const sidebarOverlay = document.getElementById('sidebar-overlay');
        function toggleSidebar() {
            sidebar.classList.toggle('open');
            sidebarOverlay.classList.toggle('active');
        }

        // --- Show Section (Simplified for PHP rendering) ---
        // Navigation is now handled by page loads via links
        // JS might be needed if you want SPA-like behavior with AJAX later

        // --- Modal Handling ---
        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            if(modal) modal.style.display = "flex"; // Use style directly or add 'active' class
        }
        function closeModal(modalId) {
             const modal = document.getElementById(modalId);
             if(modal) modal.style.display = "none";
             // Reset loading state for buttons inside the closed modal if needed
        }
        // Close modal on overlay click
        window.onclick = function(event) {
            document.querySelectorAll('.modal').forEach(modal => {
                if (event.target == modal) closeModal(modal.id);
            });
        }

       // --- Toast Notification ---
       function showToast(message, type = 'success') {
            const toastContainer = document.getElementById('toast-container');
            if (!toastContainer) return; // Ensure container exists

            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`; // Add animation class

            let iconHtml = '';
            if (type === 'success') iconHtml = '<i class="fas fa-check-circle mr-2"></i>';
            else if (type === 'error') iconHtml = '<i class="fas fa-times-circle mr-2"></i>';
            else if (type === 'warning') iconHtml = '<i class="fas fa-exclamation-triangle mr-2"></i>';
            else iconHtml = '<i class="fas fa-info-circle mr-2"></i>';

            toast.innerHTML = iconHtml + message;
            toastContainer.appendChild(toast);

            // Trigger reflow to enable transition
            void toast.offsetWidth;

            // Add 'show' class to slide in
            toast.classList.add('show');

            setTimeout(() => {
                toast.classList.remove('show'); // Slide out
                setTimeout(() => {
                    if (toast.parentNode === toastContainer) {
                        toastContainer.removeChild(toast);
                    }
                }, 300); // Wait for slide out transition (0.3s)
            }, 3500);
       }

       // --- Button Loading State ---
       function disableButton(button, loadingText = "Đang xử lý...") {
            if (!button) return;
            button.disabled = true;
            button.classList.add('opacity-75', 'cursor-wait');
            button.dataset.originalText = button.innerHTML;
            button.innerHTML = `<i class="fas fa-spinner fa-spin mr-2"></i> ${loadingText}`;
       }
       function enableButton(button) {
            if (!button) return;
            button.disabled = false;
            button.classList.remove('opacity-75', 'cursor-wait');
            if (button.dataset.originalText) {
                button.innerHTML = button.dataset.originalText;
                delete button.dataset.originalText;
            }
       }

        // --- Specific JS Functions (Adapt for PHP/AJAX) ---

        // Example: Handle Reject Modal Submission (Needs AJAX or form setup)
        function setupRejectModal(transactionId, formActionUrl) {
            const modal = document.getElementById('rejectTransactionModal');
            if (!modal) return;
            modal.querySelector('#reject-modal-title').innerText = transactionId;
            const form = modal.querySelector('#reject-transaction-form');
            form.action = formActionUrl; // Set the correct action URL from PHP
            form.querySelector('#rejection-reason').value = ''; // Clear reason
            form.querySelector('#confirm-reject-btn').onclick = () => { // Add loading state on click
                 disableButton(form.querySelector('#confirm-reject-btn'));
                 // Optionally use fetch API here instead of direct form submit
                 // fetch(formActionUrl, { method: 'POST', body: new FormData(form), headers: {'X-CSRF-TOKEN': csrfToken} })
                 // .then(...) handle response
                 form.submit(); // Submit form normally
            };
            openModal('rejectTransactionModal');
        }

        // Example: View Proof (uses data passed from PHP)
        function viewProofModal(transactionId, proofUrl) {
             // Permission check should ideally happen server-side before rendering the button
             document.getElementById('proof-modal-title').innerText = transactionId;
             const imgElement = document.getElementById('proof-modal-image');
             const placeholder = 'https://via.placeholder.com/400x300/eee/ccc?text=Minh+Chung';
             imgElement.src = proofUrl || placeholder;
             imgElement.onerror = () => { imgElement.src = placeholder; };
             openModal('viewProofModal');
        }

        // Example: Toggle User Status (Needs AJAX call)
        async function toggleUserStatus(userId, buttonElement) {
             // Permission check should happen server-side before rendering button,
             // but double-check here for safety if calling an API.
             if (!hasPermission('user_status_toggle')) { showToast('Không có quyền!', 'error'); return; }

             const icon = buttonElement.querySelector('i');
             const isActive = icon ? icon.classList.contains('fa-lock') : false; // Determine current state from icon
             const actionText = isActive ? 'KHÓA' : 'MỞ KHÓA';
             const confirmMsg = `Bạn chắc chắn muốn ${actionText} tài khoản người dùng ${userId}?`;

             if (confirm(confirmMsg)) {
                 disableButton(buttonElement);
                 showToast(`Đang ${actionText.toLowerCase()} user ${userId}...`, 'info');

                 try {
                     const response = await fetch(url(`admin/users/${userId}/toggle-status`), {
                         method: 'POST',
                         headers: {
                             'Content-Type': 'application/json', // Or form data if preferred
                             'X-CSRF-TOKEN': csrfToken,
                             'Accept': 'application/json'
                         },
                         body: JSON.stringify({}) // Send empty body or specific action if needed
                     });

                     const result = await response.json();

                     if (response.ok && result.success) {
                         showToast(result.message || `Đã ${actionText.toLowerCase()} user ${userId} thành công!`, 'success');
                         // Update UI dynamically based on result.newStatus
                         const statusBadge = buttonElement.closest('tr')?.querySelector('.badge');
                         if (statusBadge) {
                             statusBadge.textContent = result.newStatus === 'active' ? 'Hoạt động' : 'Đã khóa';
                             statusBadge.className = `badge ${result.newStatus === 'active' ? 'badge-green' : 'badge-red'}`;
                         }
                         if (icon) {
                              const isNowActive = result.newStatus === 'active';
                              icon.className = `fas ${isNowActive ? 'fa-lock' : 'fa-lock-open'} text-[11px] md:text-xs`;
                              buttonElement.title = isNowActive ? 'Khóa' : 'Mở khóa';
                              icon.classList.remove('text-red-600', 'text-green-600');
                              icon.classList.add(isNowActive ? 'text-red-600' : 'text-green-600'); // Lock icon is red when active
                              buttonElement.classList.remove('hover:text-red-700', 'hover:text-green-700');
                              buttonElement.classList.add(isNowActive ? 'hover:text-red-700' : 'hover:text-green-700');
                         }

                     } else {
                         showToast(result.message || `Lỗi khi ${actionText.toLowerCase()} user ${userId}!`, 'error');
                     }
                 } catch (error) {
                     console.error('Toggle status error:', error);
                     showToast('Có lỗi xảy ra, vui lòng thử lại.', 'error');
                 } finally {
                     enableButton(buttonElement);
                 }
             }
         }

        // --- Referral Tab Switching ---
        function switchReferralTab(event, tabId) {
            event.preventDefault();
            // Permission check (e.g., for settings tab) should be done in PHP when rendering
            document.querySelectorAll('.referral-content').forEach(c => { c.style.display = 'none'; });
            document.querySelectorAll('.referral-tab').forEach(t => {
                 t.classList.remove('border-primary-500', 'text-primary-600');
                 t.classList.add('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
                 t.removeAttribute('aria-current');
            });
            const contentToShow = document.getElementById(tabId);
            if (contentToShow) { contentToShow.style.display = 'block'; }
            event.currentTarget.classList.add('border-primary-500', 'text-primary-600');
            event.currentTarget.classList.remove('border-transparent', 'text-gray-500');
            event.currentTarget.setAttribute('aria-current', 'page');
        }

        // --- Init ---
        document.addEventListener('DOMContentLoaded', () => {
            // Apply JS-based permission checks if needed for dynamic elements not handled by PHP
            // applyJsPermissions();

            // Initialize things like charts if you add them

            // Show initial flash messages as toasts
            <?php if ($flash_success = App\Core\Auth::getFlash('success')): ?>
                showToast('<?= e($flash_success) ?>', 'success');
            <?php endif; ?>
            <?php if ($flash_error = App\Core\Auth::getFlash('error')): ?>
                showToast('<?= e($flash_error) ?>', 'error');
            <?php endif; ?>
            <?php if ($flash_info = App\Core\Auth::getFlash('info')): ?>
                showToast('<?= e($flash_info) ?>', 'info');
            <?php endif; ?>


             // Setup referral tabs if on the referral page
             if (document.getElementById('referral-tabs')) {
                 const initialReferralTabLink = document.querySelector('#referral-tabs .referral-tab[aria-current="page"]');
                 if (initialReferralTabLink) {
                     const initialTabId = initialReferralTabLink.getAttribute('data-tab');
                      document.querySelectorAll('.referral-content').forEach(content => {
                          content.style.display = (content.id === initialTabId) ? 'block' : 'none';
                     });
                 }
             }

             console.log('Admin Layout JS Initialized.');
        });

    </script>
</body>
</html>
