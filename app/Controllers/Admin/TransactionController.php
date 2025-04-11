<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\SurveyAccount;
use App\Models\Station; // Assuming a Station model exists
use PDOException;

class TransactionController {

    public function __construct() {
        Auth::authenticateAdmin();
    }

    /**
     * Display a list of payment transactions.
     */
    public function index(): void {
        Auth::authorize('invoice_management');

        $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
        $search = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_SPECIAL_CHARS); // Search payment ID, reg ID, user email
        $status = filter_input(INPUT_GET, 'status', FILTER_SANITIZE_SPECIAL_CHARS); // pending, approved, rejected
        $startDate = filter_input(INPUT_GET, 'start_date', FILTER_SANITIZE_SPECIAL_CHARS); // YYYY-MM-DD
        $endDate = filter_input(INPUT_GET, 'end_date', FILTER_SANITIZE_SPECIAL_CHARS); // YYYY-MM-DD
        $limit = 15;
        $offset = ($page - 1) * $limit;

        $filters = [];
        if ($search) $filters['search'] = $search;
        if ($status) $filters['status'] = $status;
        if ($startDate) $filters['start_date'] = $startDate;
        if ($endDate) $filters['end_date'] = $endDate;

        try {
            $payments = Payment::getAllAdmin($filters, $limit, $offset);
            $totalPayments = Payment::countAllAdmin($filters);
            $totalPages = ceil($totalPayments / $limit);
        } catch (PDOException $e) {
            error_log("Transaction List Error: " . $e->getMessage());
            Auth::setFlash('error', 'Lỗi khi tải danh sách giao dịch.');
            $payments = [];
            $totalPayments = 0;
            $totalPages = 0;
        }

        view('admin.transactions.index', [
            'title' => 'Quản lý Giao dịch & Duyệt TT',
            'activeSection' => 'admin-invoice-management',
            'payments' => $payments,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalPayments' => $totalPayments,
            'filters' => ['search' => $search, 'status' => $status, 'start_date' => $startDate, 'end_date' => $endDate]
        ], 'admin');
    }

    /**
     * Approve a pending payment transaction.
     */
    public function approve(int $id): void {
        Auth::authorize('transaction_approve');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token()) {
            Auth::setFlash('error', 'Yêu cầu không hợp lệ.');
            redirect('admin/transactions');
        }

        $db = Database::getInstance(); // Get PDO instance for transaction

        try {
            $payment = Payment::findById($id);
            if (!$payment || $payment->confirmed || $payment->rejection_reason) {
                Auth::setFlash('error', 'Giao dịch không hợp lệ hoặc đã được xử lý.');
                redirect('admin/transactions');
                return;
            }

            $registration = Registration::findById($payment->registration_id);
            if (!$registration || !in_array($registration->status, ['pending_confirmation', 'rejected'])) { // Allow approving 'rejected' ones if needed?
                Auth::setFlash('error', 'Đăng ký liên kết không hợp lệ (' . $registration->id . '). Trạng thái: ' . $registration->status);
                redirect('admin/transactions');
                return;
            }

            // --- Database Transaction ---
            $db->beginTransaction();

            // 1. Confirm Payment
            if (!$payment->confirm(Auth::adminUserId())) {
                throw new \Exception("Không thể xác nhận thanh toán.");
            }

            // 2. Activate Registration
            $registration->status = 'active';
            // Assign station if not already assigned (Simple: first active station in location)
            if (empty($registration->station_id) && !empty($registration->location_id)) {
                $station = Station::findFirstActiveByLocation($registration->location_id); // Need this method in Station model
                if ($station) {
                    $registration->station_id = $station->id;
                } else {
                     // Handle case where no station is available - maybe keep registration pending?
                     error_log("No active station found for location ID: " . $registration->location_id . " for registration " . $registration->id);
                    // throw new \Exception("Không tìm thấy trạm phát sóng hoạt động cho khu vực.");
                    Auth::setFlash('warning', 'Đã duyệt GD nhưng chưa tìm thấy trạm phát sóng phù hợp cho khu vực. TK chưa thể sử dụng.');
                    // Don't throw exception, let registration update proceed but maybe set a different status?
                }
            }
             if (!$registration->update()) { // Update status and station_id
                 throw new \Exception("Không thể cập nhật trạng thái đăng ký.");
             }

             // 3. Create Survey Account(s) - only if station was assigned
             if (!empty($registration->station_id)) {
                 // Check if accounts already exist for this registration (prevent duplicates)
                 $existingAccounts = SurveyAccount::findByRegistrationId($registration->id);
                 if (empty($existingAccounts)) {
                     $station = Station::findById($registration->station_id); // Fetch assigned station details
                     if (!$station) {
                         throw new \Exception("Không tìm thấy thông tin trạm phát sóng đã gán.");
                     }

                     for ($i = 0; $i < $registration->num_account; $i++) {
                         $surveyAcc = new SurveyAccount();
                         $surveyAcc->registration_id = $registration->id;
                         // Generate unique username (e.g., REGID_U1, REGID_U2)
                         $surveyAcc->username_acc = $registration->id . '_U' . ($i + 1);
                         // Generate random password (store plain or hash depending on system)
                         $surveyAcc->password_acc = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8);
                         $surveyAcc->caster_ip = $station->ip;
                         $surveyAcc->caster_port = $station->port;
                         $surveyAcc->mount_point = $station->mount_point ?: strtoupper($surveyAcc->username_acc); // Default mount point
                         $surveyAcc->active = true;

                         if (!$surveyAcc->create()) {
                             throw new \Exception("Không thể tạo tài khoản đo đạc #" . ($i + 1));
                         }
                     }
                 }
             } else {
                  // Log or notify that accounts couldn't be created due to missing station
             }

            // --- TODO: Trigger Referral Commission ---
            if ($registration->collaborator_id) {
                 // Calculate commission (e.g., 10% of base_price)
                 $commissionAmount = $registration->base_price * 0.10; // Get rate from settings
                 if ($commissionAmount > 0) {
                     $referral = new Referral();
                     $referral->referrer_user_id = $registration->collaborator_id;
                     $referral->referred_user_id = $registration->user_id;
                     $referral->registration_id = $registration->id;
                     $referral->commission_amount = $commissionAmount;
                     $referral->status = 'pending'; // Pending payout
                     if (!$referral->create()) {
                          // Log error, but don't necessarily roll back the main transaction
                          error_log("Failed to create referral commission for registration ID: " . $registration->id);
                     }
                 }
            }
            // --- End Referral ---

            $db->commit(); // Everything successful
            Auth::setFlash('success', 'Đã duyệt giao dịch và kích hoạt tài khoản thành công.');

        } catch (\Exception $e) { // Catch both PDOException and general Exception
            $db->rollBack(); // Roll back changes on any error
            error_log("Transaction Approve Error (ID: $id): " . $e->getMessage());
            Auth::setFlash('error', 'Lỗi khi duyệt giao dịch: ' . $e->getMessage());
        }

        redirect('admin/transactions');
    }

    /**
     * Reject a pending payment transaction.
     */
    public function reject(int $id): void {
        Auth::authorize('transaction_reject');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token()) {
            Auth::setFlash('error', 'Yêu cầu không hợp lệ.');
            redirect('admin/transactions');
        }

        $reason = trim($_POST['reason'] ?? '');
        if (empty($reason)) {
            Auth::setFlash('error', 'Vui lòng nhập lý do từ chối.');
            // Ideally redirect back to the list but keep modal open via JS state,
            // or show reason input on the list page itself.
            redirect('admin/transactions'); // Simple redirect for now
            return;
        }

        $db = Database::getInstance();
        try {
             $payment = Payment::findById($id);
             if (!$payment || $payment->confirmed || $payment->rejection_reason) {
                 Auth::setFlash('error', 'Giao dịch không hợp lệ hoặc đã được xử lý.');
                 redirect('admin/transactions');
                 return;
             }

             $registration = Registration::findById($payment->registration_id);
             if (!$registration) { // Should exist if payment exists, but check anyway
                  Auth::setFlash('error', 'Không tìm thấy đăng ký liên kết.');
                  redirect('admin/transactions');
                  return;
             }

             $db->beginTransaction();

             // 1. Reject Payment
             if (!$payment->reject(Auth::adminUserId(), $reason)) {
                 throw new \Exception("Không thể từ chối thanh toán.");
             }

             // 2. Update Registration Status
             $registration->status = 'rejected';
             if (!$registration->updateStatus('rejected')) { // Use dedicated status update
                 throw new \Exception("Không thể cập nhật trạng thái đăng ký.");
             }

             // 3. TODO: Optionally cancel related pending referral commission
             // $referral = Referral::findByRegistrationId($registration->id);
             // if ($referral && $referral->status === 'pending') {
             //     $referral->updateStatus('cancelled');
             // }

             $db->commit();
             Auth::setFlash('success', 'Đã từ chối giao dịch.');

        } catch (\Exception $e) {
             $db->rollBack();
             error_log("Transaction Reject Error (ID: $id): " . $e->getMessage());
             Auth::setFlash('error', 'Lỗi khi từ chối giao dịch: ' . $e->getMessage());
        }

        redirect('admin/transactions');
    }

     // Add methods to view details if needed, e.g., showing proof image URL
     // public function showProof(int $id) { ... fetch payment, return JSON with image path ... }
}