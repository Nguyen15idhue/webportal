<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? APP_NAME) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        // Tailwind config from user HTML
        tailwind.config = { /* ... */ }
    </script>
    <style>
         /* --- CSS Tùy chỉnh cơ bản (Copy from user HTML <style> tag) --- */
         .map-container { /* ... */ }
         @media (min-width: 768px) { .map-container { /* ... */ } }
         .content-section { display: none; } /* Hide all sections initially */
         .content-section.active { display: block; }

         /* Kiểu cho Nav Item Active */
         .nav-item.active { /* ... */ }
         .nav-item.active i { /* ... */ }

         /* Badge Styles */
         .badge { /* ... */ }
         .badge-green { /* ... */ } /* etc. */

         /* Style cho Mobile Sidebar Transition */
         #sidebar { transition: transform 0.3s ease-in-out; }
         #sidebar.open { transform: translateX(0); }
         #sidebar-overlay { transition: opacity 0.3s ease-in-out; opacity: 0; pointer-events: none; }
         #sidebar-overlay.open { opacity: 1; pointer-events: auto; }

         /* Input Styles (Ensure consistency) */
         input[type="text"], input[type="email"], input[type="tel"], input[type="date"], input[type="number"], input[type="password"], textarea, select {
            @apply w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-300 focus:border-primary-500 transition duration-150 ease-in-out shadow-sm text-sm;
         }
         input[type="file"] {
            @apply block w-full text-sm text-gray-500 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-300 file:mr-4 file:py-2 file:px-4 file:rounded-l-lg file:border-0 file:text-sm file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100;
         }
         button, .btn { @apply transition duration-150 ease-in-out; }
          /* Basic Button */
         .btn-primary { @apply py-2.5 px-5 rounded-lg font-medium text-sm inline-flex items-center justify-center gap-1 bg-primary-500 hover:bg-primary-600 text-white shadow; }
         .btn-secondary { @apply py-2 px-4 rounded-lg font-medium text-sm inline-flex items-center justify-center gap-1 bg-gray-200 hover:bg-gray-300 text-gray-800; }

        /* Toast Container Style */
        #toast-container {
             position: fixed; bottom: 20px; right: 20px; z-index: 1050; display: flex;
             flex-direction: column; align-items: flex-end; gap: 10px;
        }
        #toast-container .toast {
            padding: 10px 15px; border-radius: 6px; color: white; font-size: 14px;
            display: inline-flex; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.15);
            opacity: 0; transform: translateY(20px); transition: opacity 0.3s ease-out, transform 0.3s ease-out;
            max-width: 300px; word-break: break-word;
        }
        #toast-container .toast.show {
            opacity: 1; transform: translateY(0);
        }
        #toast-container .toast-success { background-color: rgba(34, 197, 94, 0.95); }
        #toast-container .toast-error { background-color: rgba(239, 68, 68, 0.95); }
        #toast-container .toast-warning { background-color: rgba(245, 158, 11, 0.95); }
        #toast-container .toast-info { background-color: rgba(59, 130, 246, 0.95); }
    </style>
</head>
<body class="bg-gray-100 text-gray-800 text-sm font-sans">

    <div class="relative min-h-screen lg:flex">

        <!-- Overlay for Mobile Sidebar -->
        <div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-20 hidden lg:hidden" onclick="toggleSidebar()"></div>

        <!-- Sidebar -->
        <div id="sidebar" class="fixed inset-y-0 left-0 w-64 bg-white shadow-md flex flex-col shrink-0 z-30 transform -translate-x-full lg:translate-x-0 lg:static lg:inset-auto lg:z-auto transition-transform duration-300 ease-in-out">
            <!-- Sidebar Header -->
            <div class="flex items-center justify-between p-4 border-b border-gray-200">
                <h1 class="text-xl font-bold text-primary-700 flex items-center gap-2">
                    <i class="fas fa-ruler-combined"></i> <span>Đo đạc</span>
                </h1>
                <button class="text-gray-500 hover:text-gray-700 lg:hidden" onclick="toggleSidebar()">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <!-- Sidebar User Info -->
            <div class="p-4">
                <div class="flex items-center p-2 rounded-lg bg-gray-50 border border-gray-200">
                    <div class="w-10 h-10 rounded-full bg-primary-500 flex items-center justify-center text-white shrink-0"><i class="fas fa-user"></i></div>
                    <div class="ml-3 overflow-hidden">
                        <p id="user-sidebar-name" class="font-semibold text-gray-900 truncate text-base"><?= e(App\Core\Auth::session('user_fullname', 'Khách')) ?></p>
                        <p class="text-xs text-gray-500">Khách hàng</p>
                    </div>
                </div>
            </div>
            <!-- Sidebar Navigation -->
            <div class="flex-grow overflow-y-auto p-4 pt-0">
                <nav class="space-y-1">
                    <?php if (can('user_dashboard')): ?>
                    <a href="<?= url('dashboard') ?>" class="nav-item flex items-center p-3 rounded-lg text-gray-700 hover:bg-primary-50 cursor-pointer transition duration-150 ease-in-out <?= ($activeSection ?? '') === 'dashboard' ? 'active' : '' ?>" data-section="dashboard"> <i class="fas fa-tachometer-alt w-5 h-5 mr-3 text-primary-600"></i> Dashboard </a>
                    <?php endif; ?>
                    <?php if (can('user_map')): ?>
                    <a href="<?= url('map') ?>" class="nav-item flex items-center p-3 rounded-lg text-gray-700 hover:bg-primary-50 cursor-pointer transition duration-150 ease-in-out <?= ($activeSection ?? '') === 'map' ? 'active' : '' ?>" data-section="map"> <i class="fas fa-map-marked-alt w-5 h-5 mr-3 text-primary-600"></i> Map hiển thị </a>
                    <?php endif; ?>
                    <?php if (can('user_order')): ?>
                    <a href="<?= url('order/packages') ?>" class="nav-item flex items-center p-3 rounded-lg text-gray-700 hover:bg-primary-50 cursor-pointer transition duration-150 ease-in-out <?= ($activeSection ?? '') === 'buy' ? 'active' : '' ?>" data-section="buy"> <i class="fas fa-shopping-cart w-5 h-5 mr-3 text-primary-600"></i> Mua tài khoản </a>
                    <?php endif; ?>
                    <?php if (can('user_accounts')): ?>
                    <a href="<?= url('accounts') ?>" class="nav-item flex items-center p-3 rounded-lg text-gray-700 hover:bg-primary-50 cursor-pointer transition duration-150 ease-in-out <?= ($activeSection ?? '') === 'account-management' ? 'active' : '' ?>" data-section="account-management"> <i class="fas fa-tasks w-5 h-5 mr-3 text-primary-600"></i> Quản lý tài khoản </a>
                    <?php endif; ?>
                     <?php if (can('user_transactions')): ?>
                    <a href="<?= url('transactions') ?>" class="nav-item flex items-center p-3 rounded-lg text-gray-700 hover:bg-primary-50 cursor-pointer transition duration-150 ease-in-out <?= ($activeSection ?? '') === 'transaction-management' ? 'active' : '' ?>" data-section="transaction-management"> <i class="fas fa-file-invoice-dollar w-5 h-5 mr-3 text-primary-600"></i> Quản lý giao dịch </a>
                     <?php endif; ?>
                    <?php if (can('user_referral')): ?>
                    <a href="<?= url('referrals') ?>" class="nav-item flex items-center p-3 rounded-lg text-gray-700 hover:bg-primary-50 cursor-pointer transition duration-150 ease-in-out <?= ($activeSection ?? '') === 'referral' ? 'active' : '' ?>" data-section="referral"> <i class="fas fa-users w-5 h-5 mr-3 text-primary-600"></i> Chương trình giới thiệu </a>
                     <?php endif; ?>

                    <!-- Trợ giúp -->
                    <?php if (can('user_info')): ?>
                    <div class="pt-4 mt-4 border-t border-gray-200">
                         <p class="text-xs font-semibold text-gray-500 uppercase px-3 mb-2">Trợ giúp</p>
                         <nav class="space-y-1">
                            <a href="<?= url('guide') ?>" class="nav-item flex items-center p-3 rounded-lg text-gray-700 hover:bg-primary-50 cursor-pointer transition duration-150 ease-in-out <?= ($activeSection ?? '') === 'user-guide' ? 'active' : '' ?>" data-section="user-guide"> <i class="fas fa-book-open w-5 h-5 mr-3 text-primary-600"></i> Hướng dẫn sử dụng </a>
                            <a href="<?= url('support') ?>" class="nav-item flex items-center p-3 rounded-lg text-gray-700 hover:bg-primary-50 cursor-pointer transition duration-150 ease-in-out <?= ($activeSection ?? '') === 'support' ? 'active' : '' ?>" data-section="support"> <i class="fas fa-headset w-5 h-5 mr-3 text-primary-600"></i> Hỗ trợ </a>
                         </nav>
                    </div>
                    <?php endif; ?>

                    <!-- Cài đặt -->
                     <?php if (can('user_settings')): ?>
                    <div class="pt-4 mt-4 border-t border-gray-200">
                        <p class="text-xs font-semibold text-gray-500 uppercase px-3 mb-2">Cài đặt</p>
                        <nav class="space-y-1">
                            <a href="<?= url('settings/profile') ?>" class="nav-item flex items-center p-3 rounded-lg text-gray-700 hover:bg-primary-50 cursor-pointer transition duration-150 ease-in-out <?= ($activeSection ?? '') === 'profile' ? 'active' : '' ?>" data-section="profile"> <i class="fas fa-user-circle w-5 h-5 mr-3 text-primary-600"></i> Thông tin cá nhân </a>
                            <a href="<?= url('settings/payment-method') ?>" class="nav-item flex items-center p-3 rounded-lg text-gray-700 hover:bg-primary-50 cursor-pointer transition duration-150 ease-in-out <?= ($activeSection ?? '') === 'payment-method-info' ? 'active' : '' ?>" data-section="payment-method-info"> <i class="fas fa-credit-card w-5 h-5 mr-3 text-primary-600"></i> Thông tin thanh toán </a>
                            <a href="<?= url('settings/invoice-info') ?>" class="nav-item flex items-center p-3 rounded-lg text-gray-700 hover:bg-primary-50 cursor-pointer transition duration-150 ease-in-out <?= ($activeSection ?? '') === 'invoice-info' ? 'active' : '' ?>" data-section="invoice-info"> <i class="fas fa-file-alt w-5 h-5 mr-3 text-primary-600"></i> Thông tin xuất hóa đơn </a>
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

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col overflow-hidden">

            <!-- Mobile/Tablet Header -->
            <header class="lg:hidden sticky top-0 z-10 bg-white shadow-md">
                <div class="container mx-auto px-4 py-3 flex items-center justify-between">
                    <button class="text-primary-700 hover:text-primary-900" onclick="toggleSidebar()">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    <h1 class="text-lg font-bold text-primary-700">
                        <i class="fas fa-ruler-combined mr-2"></i> <?= e($pageTitle ?? $title ?? APP_NAME) ?>
                    </h1>
                     <div class="w-6"></div> <!-- Placeholder -->
                </div>
            </header>

            <!-- Main Content Scrollable Area -->
            <main class="flex-1 overflow-y-auto bg-gray-100 p-4 sm:p-6 lg:p-8">
                 <!-- Flash Messages -->
                 <?php if ($flash_success = App\Core\Auth::getFlash('success')): ?>
                    <div class="alert alert-success mb-4" role="alert"><?= e($flash_success) ?></div>
                 <?php endif; ?>
                 <?php if ($flash_error = App\Core\Auth::getFlash('error')): ?>
                    <div class="alert alert-error mb-4" role="alert"><?= e($flash_error) ?></div>
                 <?php endif; ?>
                  <?php if ($flash_info = App\Core\Auth::getFlash('info')): ?>
                    <div class="alert alert-info mb-4" role="alert"><?= e($flash_info) ?></div>
                  <?php endif; ?>

                <?= $content ?> <!-- View content injected here -->
            </main>
        </div>
    </div>

    <!-- Toast Notification Container -->
    <div id="toast-container"></div>

    <script>
        // --- Core State & Config ---
        const BASE_URL = '<?= BASE_URL ?>';
        const csrfToken = '<?= csrf_token() ?>';
        const VAT_RATE = <?= VAT_RATE ?>;

        // Pass user data needed by JS (avoid sensitive info like password hash)
        let currentUserInfo = <?= json_encode([
            'id' => App\Core\Auth::userId(),
            'fullname' => App\Core\Auth::session('user_fullname'),
            'email' => App\Core\Auth::session('user_email'),
            // Load other needed fields from Auth::user() if necessary
            'phone' => $currentUser->phone ?? null,
            'company_name' => $currentUser->company_name ?? null,
            'tax_code' => $currentUser->tax_code ?? null,
            'company_address' => $currentUser->company_address ?? null,
            'invoice_email' => $currentUser->invoice_email ?? null,
            'collaborator_code' => $currentUser->collaborator_code ?? null,
            'referred_by_collaborator_code' => $currentUser->referred_by_collaborator_code ?? null,
            'user_bank_name' => $currentUser->user_bank_name ?? null,
            'user_bank_account' => $currentUser->user_bank_account ?? null,
            'user_bank_owner' => $currentUser->user_bank_owner ?? null,
            'user_bank_branch' => $currentUser->user_bank_branch ?? null,
            'user_momo' => $currentUser->user_momo ?? null,
        ]) ?>;

         // Pass bank info for payment page
         const bankInfo = <?= json_encode($bankInfo ?? ['bankId' => '', 'accountNo' => '', 'accountName' => '', 'bankName' => 'N/A']) ?>;

        // --- Sidebar ---
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        let isSidebarOpen = false;
        function toggleSidebar() {
            isSidebarOpen = !isSidebarOpen;
            if (isSidebarOpen) {
                sidebar.classList.remove('-translate-x-full');
                overlay.classList.remove('hidden');
                overlay.classList.add('open'); // Use class for opacity control
            } else {
                sidebar.classList.add('-translate-x-full');
                overlay.classList.remove('open');
                // Delay hiding to allow opacity transition
                setTimeout(() => { overlay.classList.add('hidden'); }, 300);
            }
        }

        // --- Show Section (Simplified) ---
        // Navigation mostly handled by PHP page loads

        // --- Utility Functions ---
        function formatCurrency(amount) { return isNaN(amount) ? "0 đ" : Math.round(amount).toLocaleString('vi-VN') + ' đ'; }
        function formatDate(dateString) { /* ... JS formatDate ... */ }
        function calculateEndDate(startDateString, durationMonths) { /* ... JS calculateEndDate ... */ }
        function copyToClipboard(textToCopy) { /* ... JS copyToClipboard ... */ }
        function fallbackCopy(text) { /* ... JS fallbackCopy ... */ }

        // --- Toast ---
        function showToast(message, type = 'success') {
            const toastContainer = document.getElementById('toast-container');
            if (!toastContainer) return;
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            let iconHtml = '';
            if (type === 'success') iconHtml = '<i class="fas fa-check-circle mr-2"></i>';
            else if (type === 'error') iconHtml = '<i class="fas fa-times-circle mr-2"></i>';
            else if (type === 'warning') iconHtml = '<i class="fas fa-exclamation-triangle mr-2"></i>';
            else iconHtml = '<i class="fas fa-info-circle mr-2"></i>';
            toast.innerHTML = iconHtml + message;
            toastContainer.appendChild(toast);
            requestAnimationFrame(() => { toast.classList.add('show'); });
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => { if (toast.parentNode === toastContainer) toastContainer.removeChild(toast); }, 300);
            }, 3000);
        }
         function copyReferralCode() { /* ... JS copyReferralCode ... */ }

        // --- Button Loading ---
        function disableButton(button, loadingText = "Đang xử lý...") { /* ... */ }
        function enableButton(button) { /* ... */ }

        // --- Order Flow JS ---
        // These functions interact with the DOM of the specific order views
        // Ensure elements exist before accessing them

        function selectPackageForRegistration(packageId) {
            // Redirect to details form page, passing package ID
            window.location.href = url(`order/details?package_id=${packageId}`);
        }

        function populateDetailsLocations(locations) {
            const locationSelect = document.getElementById('details-location');
            if (!locationSelect || !locations) return;
            locationSelect.innerHTML = '<option value="">-- Chọn địa điểm --</option>';
            locations.forEach(loc => {
                const option = document.createElement('option');
                option.value = loc.id; // Assuming location object has id and province
                option.textContent = loc.province;
                 // Pre-select if needed based on data from PHP
                 // if (loc.id == '<?= $selectedLocationId ?? '' ?>') option.selected = true;
                locationSelect.appendChild(option);
            });
        }

        function updateAccountDetailsSummary() {
            const detailsForm = document.getElementById('account-details-form-element');
            if (!detailsForm) return; // Only run on details page

            const basePackagePricePerAccount = parseFloat(document.getElementById('details-package-base-price')?.value) || 0;
            const durationMonths = parseInt(document.getElementById('details-package-duration-months')?.value) || 0;
            const numAccount = parseInt(document.getElementById('details-num-account')?.value) || 1;
            const startDateString = document.getElementById('details-start-date')?.value;

            let basePrice = basePackagePricePerAccount * numAccount;
            let endDateInfo = calculateEndDate(startDateString, durationMonths);
            const vatAmount = basePrice * (VAT_RATE / 100);
            const totalPrice = basePrice + vatAmount;

             // Update display elements only if they exist
             document.getElementById('details-num-account-display')?.innerText = numAccount;
             document.getElementById('details-end-date-display')?.innerText = endDateInfo.display;
             document.getElementById('details-base-price-display')?.innerText = formatCurrency(basePrice);
             document.getElementById('details-vat-amount-display')?.innerText = formatCurrency(vatAmount);
             document.getElementById('details-total-price-display')?.innerText = formatCurrency(totalPrice);
             const totalPriceInput = document.getElementById('details-total-price-value');
             if(totalPriceInput) totalPriceInput.value = totalPrice;

             // You might store this in hidden fields for the form submission instead of a global JS var
        }

        function proceedFromDetailsToPayment() {
            const form = document.getElementById('account-details-form-element');
            if (!form) return;
            if (!form.checkValidity()) {
                form.reportValidity();
                showToast('Vui lòng điền đủ thông tin (*).', 'error');
                return false; // Prevent form submission
            }
            updateAccountDetailsSummary(); // Recalculate just before submit
            // Add any other client-side checks
            disableButton(form.querySelector('button[type=submit]')); // Disable button
            return true; // Allow form submission to PHP controller
        }

        function displayFileName(input) {
             const fileInput = document.getElementById('payment-proof');
             if (!fileInput) return false; // Only run on payment page
             const file = input.files[0];
             const fileName = file ? file.name : 'Chưa chọn file nào.';
             const fileChosenElement = document.getElementById('file-chosen');
             if(fileChosenElement) fileChosenElement.innerText = fileName;
             if(fileChosenElement) fileChosenElement.classList.remove('text-red-600');

             if (file) {
                 if (file.size > 5 * 1024 * 1024) { showToast('Dung lượng file quá lớn (<5MB).', 'error'); input.value = ''; if(fileChosenElement){ fileChosenElement.innerText = 'File quá lớn! Chọn lại.'; fileChosenElement.classList.add('text-red-600'); } return false; }
                 const allowedTypes = ['image/png', 'image/jpeg', 'image/jpg'];
                 if (!allowedTypes.includes(file.type)) { showToast('Loại file không hợp lệ (PNG, JPG).', 'error'); input.value = ''; if(fileChosenElement){ fileChosenElement.innerText = 'File không hợp lệ! Chọn lại.'; fileChosenElement.classList.add('text-red-600'); } return false; }
                 return true;
             }
             return false;
        }

         function submitPaymentProof() {
            const form = document.getElementById('payment-proof-form'); // Assume form has this ID
            if (!form) return;
             const fileInput = document.getElementById('payment-proof');
             const issueInvoiceCheckbox = document.getElementById('payment-issue-invoice');

             if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
                 showToast('Vui lòng chọn file minh chứng!', 'error');
                 const fileChosenElement = document.getElementById('file-chosen');
                 if(fileChosenElement) {
                      fileChosenElement.classList.add('text-red-600');
                      fileChosenElement.innerText = 'Vui lòng chọn file!';
                 }
                 return false;
             }
             if (!displayFileName(fileInput)) { return false; }

              // Client-side check for invoice info if requested (optional, PHP must validate too)
             if (issueInvoiceCheckbox && issueInvoiceCheckbox.checked) {
                if (!currentUserInfo.company_name || !currentUserInfo.tax_code || !currentUserInfo.company_address || !currentUserInfo.invoice_email) {
                    if(confirm('Yêu cầu hóa đơn nhưng thông tin công ty chưa đủ. Đi đến trang cài đặt?')) {
                         window.location.href = url('settings/invoice-info');
                         return false; // Prevent submission
                    } else {
                         showToast('Vui lòng cập nhật Thông tin xuất hóa đơn trong Cài đặt.', 'warning');
                         return false; // Prevent submission
                    }
                }
             }

             const submitButton = form.querySelector('button[type=submit]'); // Find the submit button
             if (submitButton) disableButton(submitButton);
             form.submit(); // Submit the form to the PHP controller
        }

        // --- Initialization ---
        document.addEventListener("DOMContentLoaded", () => {
            // Update user name in sidebar if it exists
            const sidebarName = document.getElementById('user-sidebar-name');
            if (sidebarName && currentUserInfo.fullname) {
                 sidebarName.innerText = currentUserInfo.fullname;
            }

             // Run specific JS only if on the corresponding page (check for element existence)
             if (document.getElementById('account-details-form-element')) {
                 populateDetailsLocations(<?= json_encode($locations ?? []) ?>); // Pass locations from PHP controller
                 updateAccountDetailsSummary(); // Initial calculation
                 // Add listeners for form changes
                 document.getElementById('details-num-account')?.addEventListener('input', updateAccountDetailsSummary);
                 document.getElementById('details-start-date')?.addEventListener('change', updateAccountDetailsSummary);
             }
             if (document.getElementById('payment-proof')) {
                 // Setup for payment page if needed
             }
             if (document.getElementById('referralCodeInput')) {
                // Update referral code input value (passed via currentUserInfo)
                 document.getElementById('referralCodeInput').value = currentUserInfo.collaborator_code || 'N/A';
                 // Display referrer info (passed via $referrer variable in PHP view)
                 const referrerInfoDiv = document.getElementById('referrer-info-display');
                 if(referrerInfoDiv && '<?= !empty($referrer) ?>') {
                    const referrer = <?= json_encode($referrer ?? null) ?>;
                     if(referrer) {
                          referrerInfoDiv.innerHTML = `<div class="flex items-center gap-3 p-3 bg-gray-100 rounded-md border border-gray-200"><div class="w-8 h-8 rounded-full bg-primary-100 flex items-center justify-center text-primary-600 shrink-0 text-xs"><i class="fas fa-user-check"></i></div><div><p class="text-sm font-medium text-gray-800">${referrer.name}</p><p class="text-xs text-gray-500">Mã: <span class="font-medium text-gray-600">${referrer.code}</span></p></div></div>`;
                     }
                 } else if (referrerInfoDiv && currentUserInfo.referred_by_collaborator_code) {
                    referrerInfoDiv.innerHTML = `<p class="text-sm text-gray-500 italic p-3 bg-gray-100 rounded-md border border-gray-200">Được giới thiệu bởi mã: <span class="font-medium">${currentUserInfo.referred_by_collaborator_code}</span>.</p>`;
                 } else if (referrerInfoDiv) {
                     referrerInfoDiv.innerHTML = `<p class="text-sm text-gray-500 italic p-3 bg-gray-100 rounded-md border border-gray-200">Không đăng ký qua mã giới thiệu.</p>`;
                 }
             }

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

             console.log('User Layout JS Initialized.');
        });

    </script>

</body>
</html>