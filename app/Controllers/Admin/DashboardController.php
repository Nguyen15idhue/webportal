<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Models\User;
use App\Models\Registration;
use App\Models\SurveyAccount;
use App\Models\Payment;
use App\Models\Referral;
// use App\Models\ActivityLog; // Optional: If you create an activity log model

class DashboardController {

    public function __construct() {
        // Ensure the user is an authenticated admin for all methods in this controller
        Auth::authenticateAdmin();
    }

    /**
     * Display the admin dashboard.
     */
    public function index(): void {
        // Check if the authenticated admin has permission to view the dashboard
        Auth::authorize('dashboard');

        // --- Fetch Statistics ---
        // Note: Some complex stats might require dedicated model methods or more elaborate queries.
        // These examples use basic counts. Implement more specific methods in models as needed.

        $stats = [];
        try {
            // Total registered customers
            $stats['total_users'] = User::countAll();

            // Users who have at least one 'active' registration
            // Placeholder: This requires a more specific query in Registration model
            // $stats['users_with_packages'] = Registration::countDistinctUsersWithStatus('active');
            $stats['users_with_packages'] = Registration::countAllAdmin(['status' => 'active']); // Approximation

            // Total active survey accounts
            $stats['active_survey_accounts'] = SurveyAccount::countAllAdmin(['status' => true]);

            // Monthly Revenue (Requires dedicated method in Payment or Registration model)
            $startOfMonth = date('Y-m-01 00:00:00');
            $endOfMonth = date('Y-m-t 23:59:59');
            // $stats['monthly_revenue'] = Payment::getTotalRevenue($startOfMonth, $endOfMonth); // Placeholder
            $stats['monthly_revenue'] = Payment::countAllAdmin() * 500000; // Rough placeholder

            // Total referrers (Users who have a collaborator code and maybe referred someone)
            // $stats['total_referrers'] = User::countAllReferrers(); // Placeholder
            $stats['total_referrers'] = User::countAll(['has_collaborator_code' => true]); // Approximation if filter added

            // Total registrations made using a referral code
            // $stats['referred_registrations'] = Registration::countAllReferred(); // Placeholder
            $stats['referred_registrations'] = Registration::countAllAdmin(['has_referrer' => true]); // Approximation if filter added

            // Total commission paid out (Requires dedicated method in Referral model)
            // $stats['total_commission_paid'] = Referral::getTotalCommissionPaid(); // Placeholder
            $stats['total_commission_paid'] = Referral::countAllAdmin(['status' => 'paid']) * 50000; // Rough placeholder

        } catch (\PDOException $e) {
            error_log("Dashboard Stats Error: " . $e->getMessage());
            Auth::setFlash('error', 'Lỗi khi tải dữ liệu thống kê.');
            $stats = array_fill_keys([
                'total_users', 'users_with_packages', 'active_survey_accounts',
                'monthly_revenue', 'total_referrers', 'referred_registrations',
                'total_commission_paid'
            ], 0); // Set defaults on error
        }


        // --- Fetch Recent Activities ---
        // This should ideally come from a dedicated log table or combine queries
        // Placeholder data for now:
        $recentActivities = [
             ['icon' => 'fas fa-user-plus', 'color' => 'text-blue-500', 'description' => '<strong class="font-medium">user_moi@email.com</strong> đã đăng ký.', 'time_ago' => '10 phút trước'],
             ['icon' => 'fas fa-receipt', 'color' => 'text-yellow-600', 'description' => 'GD <strong class="font-medium">PAY-123</strong> (<strong class="font-medium">khach_vip</strong>) chờ duyệt.', 'time_ago' => '30 phút trước'],
             ['icon' => 'fas fa-check-circle', 'color' => 'text-green-500', 'description' => 'Admin <strong class="font-medium">SuperAdmin</strong> duyệt GD <strong class="font-medium">PAY-122</strong>.', 'time_ago' => '1 giờ trước'],
             ['icon' => 'fas fa-user-edit', 'color' => 'text-orange-500', 'description' => 'Admin <strong class="font-medium">Admin01</strong> cập nhật user <strong class="font-medium">demo_user</strong>.', 'time_ago' => '3 giờ trước'],
             ['icon' => 'fas fa-ban', 'color' => 'text-red-500', 'description' => 'GD <strong class="font-medium">PAY-120</strong> bị từ chối.', 'time_ago' => '5 giờ trước'],
        ];
        // Example: $recentActivities = ActivityLog::getRecentAdmin(5);


        // --- Fetch Chart Data ---
        // Placeholder data - replace with actual data fetching for charts
        $charts = [
            'new_registrations' => ['labels' => [], 'data' => []], // e.g., ['labels' => ['Mon', 'Tue', ...], 'data' => [5, 10, ...]]
            'new_referrals' => ['labels' => [], 'data' => []],
        ];

        // Render the dashboard view, passing the fetched data
        view('admin.dashboard.index', [
            'title' => 'Admin Dashboard',
            'activeSection' => 'admin-dashboard', // Used by layout to highlight sidebar
            'stats' => $stats,
            'recentActivities' => $recentActivities,
            'charts' => $charts // Pass chart data to the view for JS rendering
        ], 'admin'); // Use the 'admin' layout
    }
}