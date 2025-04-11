<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Models\User;
use App\Models\Referral;
use App\Models\Registration; // To show referral source details
use App\Models\Setting; // Assuming a Setting model for referral config
use PDOException;

class ReferralController {

    public function __construct() {
        Auth::authenticateAdmin();
    }

    /**
     * Display referral management page (tabs for Referrers, Commissions, Settings).
     */
    public function index(): void {
        Auth::authorize('referral_management');

        $tab = filter_input(INPUT_GET, 'tab', FILTER_SANITIZE_SPECIAL_CHARS) ?: 'referrers';

        // Data specific to the 'Referrers' (NGT) tab
        $referrersData = $this->getReferrersData();

        // Data specific to the 'Commissions' (Lượt GT) tab
        $commissionsData = $this->getCommissionsData();

        // Data specific to the 'Settings' tab
        $settingsData = $this->getSettingsData();


        view('admin.referrals.index', [
            'title' => 'Quản lý Chương trình giới thiệu',
            'activeSection' => 'admin-referral-management',
            'currentTab' => $tab,
            'referrers' => $referrersData['referrers'],
            'referrerPagination' => $referrersData['pagination'],
            'referrerFilters' => $referrersData['filters'],
            'commissions' => $commissionsData['commissions'],
            'commissionPagination' => $commissionsData['pagination'],
            'commissionFilters' => $commissionsData['filters'],
            'settings' => $settingsData['settings'],
            'canEditSettings' => Auth::hasPermission('referral_settings_edit') // Pass permission status
        ], 'admin');
    }

    /** Helper: Get data for the Referrers tab */
    private function getReferrersData(): array {
        $page = filter_input(INPUT_GET, 'rpage', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
        $search = filter_input(INPUT_GET, 'rsearch', FILTER_SANITIZE_SPECIAL_CHARS);
        $limit = 15;
        $offset = ($page - 1) * $limit;

        $filters = [];
        if ($search) $filters['search'] = $search; // Search user email or code

        $referrers = [];
        $totalReferrers = 0;

        try {
            // Need a dedicated method in User model or a specific query here
            // to find users who ARE referrers (have non-null collaborator_code)
            // and fetch their pending/total commission. This is complex.

            // --- Placeholder / Simplification ---
            // Get all users with collaborator codes (approximation)
            $referrers = User::getAll(['has_collaborator_code' => true, 'search' => $search], $limit, $offset);
            $totalReferrers = User::countAll(['has_collaborator_code' => true, 'search' => $search]);

            // Fetch stats for each referrer (inefficient - better to join in SQL)
            foreach ($referrers as $referrer) {
                 $referrer->pending_commission = Referral::getTotalPendingCommission($referrer->id);
                 // $referrer->total_paid_commission = Referral::getTotalPaidCommission($referrer->id); // Need model method
                 $referrer->successful_referrals_count = Referral::countByReferrerId($referrer->id); // Count how many commissions they generated
            }
            // --- End Placeholder ---

        } catch (PDOException $e) {
             error_log("Referrer List Error: " . $e->getMessage());
             Auth::setFlash('error', 'Lỗi khi tải danh sách người giới thiệu.');
        }

        return [
            'referrers' => $referrers,
            'pagination' => [
                'currentPage' => $page,
                'totalPages' => ceil($totalReferrers / $limit),
                'totalItems' => $totalReferrers,
            ],
            'filters' => ['search' => $search]
        ];
    }

    /** Helper: Get data for the Commissions tab */
    private function getCommissionsData(): array {
        $page = filter_input(INPUT_GET, 'cpage', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
        $search = filter_input(INPUT_GET, 'csearch', FILTER_SANITIZE_SPECIAL_CHARS); // Search referred email, referrer code
        $status = filter_input(INPUT_GET, 'cstatus', FILTER_SANITIZE_SPECIAL_CHARS); // pending, paid, cancelled
        $limit = 15;
        $offset = ($page - 1) * $limit;

        $filters = [];
        if ($search) $filters['search'] = $search;
        if ($status) $filters['status'] = $status;

        $commissions = [];
        $totalCommissions = 0;

        try {
            $commissions = Referral::getAllAdmin($filters, $limit, $offset);
            $totalCommissions = Referral::countAllAdmin($filters);
        } catch (PDOException $e) {
             error_log("Commission List Error: " . $e->getMessage());
             Auth::setFlash('error', 'Lỗi khi tải danh sách hoa hồng giới thiệu.');
        }

         return [
            'commissions' => $commissions,
            'pagination' => [
                'currentPage' => $page,
                'totalPages' => ceil($totalCommissions / $limit),
                'totalItems' => $totalCommissions,
            ],
            'filters' => ['search' => $search, 'status' => $status]
        ];
    }

     /** Helper: Get referral settings */
     private function getSettingsData(): array {
         // Assuming settings are stored in a 'settings' table with key-value pairs
         // Or loaded from a config file (less flexible)
         $defaults = [
             'referral_enabled' => true,
             'referral_commission_rate' => 10, // Percentage
             'referral_cookie_duration' => 30, // Days
             'referral_payout_threshold' => 100000 // VNĐ
         ];
         $settings = [];
         try {
             // Example using a Setting model
             $settingModel = new Setting(); // Assuming exists
             $settings['referral_enabled'] = $settingModel->get('referral_enabled', $defaults['referral_enabled']);
             $settings['referral_commission_rate'] = $settingModel->get('referral_commission_rate', $defaults['referral_commission_rate']);
             $settings['referral_cookie_duration'] = $settingModel->get('referral_cookie_duration', $defaults['referral_cookie_duration']);
             $settings['referral_payout_threshold'] = $settingModel->get('referral_payout_threshold', $defaults['referral_payout_threshold']);
         } catch (\Exception $e) {
             error_log("Referral Settings Load Error: " . $e->getMessage());
             Auth::setFlash('error', 'Lỗi khi tải cấu hình giới thiệu.');
             $settings = $defaults;
         }
         return ['settings' => $settings];
     }

    /**
     * Save referral settings.
     */
    public function saveSettings(): void {
        Auth::authorize('referral_settings_edit');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token()) {
            Auth::setFlash('error', 'Yêu cầu không hợp lệ.');
            redirect('admin/referrals?tab=referral-settings');
        }

        // --- Basic Validation ---
        $enabled = isset($_POST['referral_enabled']);
        $rate = filter_input(INPUT_POST, 'commission_rate', FILTER_VALIDATE_FLOAT, ['options' => ['min_range' => 0, 'max_range' => 100]]);
        $duration = filter_input(INPUT_POST, 'cookie_duration', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $threshold = filter_input(INPUT_POST, 'payout_threshold', FILTER_VALIDATE_FLOAT, ['options' => ['min_range' => 0]]);

        if ($rate === false || $duration === false || $threshold === false) {
             Auth::setFlash('error', 'Giá trị cấu hình không hợp lệ.');
             redirect('admin/referrals?tab=referral-settings');
             return;
        }

        try {
             // Example using a Setting model
             $settingModel = new Setting();
             $settingModel->set('referral_enabled', $enabled);
             $settingModel->set('referral_commission_rate', $rate);
             $settingModel->set('referral_cookie_duration', $duration);
             $settingModel->set('referral_payout_threshold', $threshold);

             Auth::setFlash('success', 'Đã lưu cấu hình giới thiệu.');
         } catch (\Exception $e) {
             error_log("Referral Settings Save Error: " . $e->getMessage());
             Auth::setFlash('error', 'Lỗi khi lưu cấu hình giới thiệu.');
         }

        redirect('admin/referrals?tab=referral-settings');
    }

    /**
     * Process commission payout for a specific referrer.
     */
    public function processPayout(int $referrerUserId): void {
        Auth::authorize('referral_payout');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token()) {
            Auth::setFlash('error', 'Yêu cầu không hợp lệ.');
            redirect('admin/referrals?tab=referrers');
        }

        try {
            $referrer = User::findById($referrerUserId);
            if (!$referrer) {
                Auth::setFlash('error', 'Không tìm thấy người giới thiệu.');
                redirect('admin/referrals?tab=referrers');
                return;
            }

            // Check if pending commission meets threshold (get threshold from settings)
            $pendingCommission = Referral::getTotalPendingCommission($referrerUserId);
            // $threshold = Setting::get('referral_payout_threshold', 100000); // Get from settings
            $threshold = 100000; // Hardcoded for now

             if ($pendingCommission < $threshold) {
                 Auth::setFlash('info', 'Hoa hồng chờ chưa đạt ngưỡng thanh toán (' . format_currency($threshold) . ').');
                 redirect('admin/referrals?tab=referrers');
                 return;
             }

            // Mark pending commissions as paid
            if (Referral::markAsPaid($referrerUserId)) {
                 // TODO: Optionally log this payout transaction internally
                 Auth::setFlash('success', 'Đã xử lý thanh toán hoa hồng cho ' . e($referrer->email));
             } else {
                 Auth::setFlash('error', 'Không thể cập nhật trạng thái hoa hồng.');
             }

        } catch (PDOException $e) {
            error_log("Referral Payout Error (User: $referrerUserId): " . $e->getMessage());
            Auth::setFlash('error', 'Lỗi cơ sở dữ liệu khi xử lý thanh toán.');
        }

        redirect('admin/referrals?tab=referrers');
    }

}

namespace App\Models;

use PDO;
use App\Core\Database;

class Setting {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function get(string $key, $default = null) {
        $stmt = $this->db->prepare("SELECT value FROM settings WHERE `key` = :key LIMIT 1");
        $stmt->bindParam(':key', $key, PDO::PARAM_STR);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ? $result['value'] : $default;
    }
    
    public function set(string $key, $value): bool {
        // Check if setting exists
        $stmt = $db->prepare("SELECT COUNT(*) FROM settings WHERE `key` = :key");
        $stmt->bindParam(':key', $key, PDO::PARAM_STR);
        $stmt->execute();
        
        if ($stmt->fetchColumn() > 0) {
            // Update existing
            $stmt = $this->db->prepare("UPDATE settings SET value = :value WHERE `key` = :key");
        } else {
            // Insert new
            $stmt = $this->db->prepare("INSERT INTO settings (`key`, value) VALUES (:key, :value)");
        }
        
        $stmt->bindParam(':key', $key, PDO::PARAM_STR);
        $stmt->bindParam(':value', $value, PDO::PARAM_STR);
        
        return $stmt->execute();
    }
}