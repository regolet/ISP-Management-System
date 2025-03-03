<?php
namespace App\Helpers;

use App\Core\Database;
use App\Models\Staff\Attendance;
use App\Models\Staff\Payment;
use App\Models\Staff\Task;

class StaffStats {
    private static $db;

    /**
     * Initialize database connection
     */
    private static function init() {
        if (!self::$db) {
            self::$db = Database::getInstance();
        }
    }

    /**
     * Get quick stats for staff dashboard
     */
    public static function getQuickStats($staffId) {
        self::init();

        return [
            'attendance' => self::getAttendanceStats($staffId),
            'payments' => self::getPaymentStats($staffId),
            'tasks' => self::getTaskStats($staffId),
            'expenses' => self::getExpenseStats($staffId)
        ];
    }

    /**
     * Get attendance stats
     */
    private static function getAttendanceStats($staffId) {
        $today = date('Y-m-d');
        
        $sql = "SELECT a.*,
                       s.name as shift_name,
                       s.start_time as shift_start,
                       s.end_time as shift_end
                FROM staff_attendance a
                LEFT JOIN shifts s ON a.shift_id = s.id
                WHERE a.staff_id = ? AND a.date = ?";
        
        $stmt = self::$db->prepare($sql);
        $stmt->bind_param('is', $staffId, $today);
        $stmt->execute();
        $attendance = $stmt->get_result()->fetch_assoc();

        // Calculate shift status
        $shiftStatus = 'not_started';
        if ($attendance) {
            $shiftStatus = $attendance['time_out'] ? 'completed' : 'ongoing';
        }

        // Get monthly summary
        $monthStart = date('Y-m-01');
        $monthEnd = date('Y-m-t');
        
        $sql = "SELECT 
                    COUNT(*) as total_days,
                    COUNT(CASE WHEN status = 'present' THEN 1 END) as present_days,
                    COUNT(CASE WHEN status = 'late' THEN 1 END) as late_days,
                    SUM(late_minutes) as total_late_minutes,
                    SUM(overtime_minutes) as total_overtime_minutes
                FROM staff_attendance
                WHERE staff_id = ? 
                AND date BETWEEN ? AND ?";
        
        $stmt = self::$db->prepare($sql);
        $stmt->bind_param('iss', $staffId, $monthStart, $monthEnd);
        $stmt->execute();
        $monthly = $stmt->get_result()->fetch_assoc();

        return [
            'today' => $attendance,
            'shift_status' => $shiftStatus,
            'monthly_summary' => $monthly
        ];
    }

    /**
     * Get payment stats
     */
    private static function getPaymentStats($staffId) {
        $today = date('Y-m-d');
        
        $sql = "SELECT COUNT(*) as count,
                       SUM(amount) as total,
                       COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
                       SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as pending_amount
                FROM staff_payments
                WHERE staff_id = ? AND DATE(payment_date) = ?";
        
        $stmt = self::$db->prepare($sql);
        $stmt->bind_param('is', $staffId, $today);
        $stmt->execute();
        $today = $stmt->get_result()->fetch_assoc();

        // Get monthly totals
        $monthStart = date('Y-m-01');
        $monthEnd = date('Y-m-t');
        
        $sql = "SELECT COUNT(*) as count,
                       SUM(amount) as total,
                       COUNT(CASE WHEN status = 'processed' THEN 1 END) as processed_count,
                       SUM(CASE WHEN status = 'processed' THEN amount ELSE 0 END) as processed_amount
                FROM staff_payments
                WHERE staff_id = ? 
                AND payment_date BETWEEN ? AND ?";
        
        $stmt = self::$db->prepare($sql);
        $stmt->bind_param('iss', $staffId, $monthStart, $monthEnd);
        $stmt->execute();
        $monthly = $stmt->get_result()->fetch_assoc();

        return [
            'today' => $today,
            'monthly' => $monthly
        ];
    }

    /**
     * Get task stats
     */
    private static function getTaskStats($staffId) {
        $sql = "SELECT 
                    COUNT(*) as total_tasks,
                    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_tasks,
                    COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as in_progress_tasks,
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_tasks,
                    COUNT(CASE WHEN priority = 'high' AND status != 'completed' THEN 1 END) as high_priority_tasks
                FROM staff_tasks
                WHERE assigned_to = ?";
        
        $stmt = self::$db->prepare($sql);
        $stmt->bind_param('i', $staffId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Get expense stats
     */
    private static function getExpenseStats($staffId) {
        $monthStart = date('Y-m-01');
        $monthEnd = date('Y-m-t');
        
        $sql = "SELECT 
                    COUNT(*) as total_claims,
                    SUM(amount) as total_amount,
                    COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved_claims,
                    SUM(CASE WHEN status = 'approved' THEN amount ELSE 0 END) as approved_amount,
                    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_claims,
                    SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as pending_amount
                FROM staff_expenses
                WHERE staff_id = ? 
                AND date BETWEEN ? AND ?";
        
        $stmt = self::$db->prepare($sql);
        $stmt->bind_param('iss', $staffId, $monthStart, $monthEnd);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Format duration in minutes to human readable string
     */
    public static function formatDuration($minutes) {
        if ($minutes < 60) {
            return "{$minutes}m";
        }
        
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        return "{$hours}h " . ($mins > 0 ? "{$mins}m" : "");
    }

    /**
     * Format currency amount
     */
    public static function formatCurrency($amount) {
        return '$' . number_format($amount, 2);
    }
}
