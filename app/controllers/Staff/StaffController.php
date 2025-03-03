<?php
namespace App\Controllers\Staff;

use App\Core\Controller;
use App\Models\Staff\Staff;

class StaffController extends Controller {
    private $staffModel;

    public function __construct() {
        parent::__construct();
        $this->staffModel = new Staff();
    }

    // Staff Dashboard
    public function dashboard() {
        if (!$this->isAuthenticated() || $_SESSION['user_role'] !== 'staff') {
            return $this->redirect('/login');
        }

        $staffId = $_SESSION['user_id'];
        $staff = $this->staffModel->find($staffId);

        // Get today's attendance
        $todayAttendance = $this->staffModel->getAttendance(
            date('Y-m-d'),
            date('Y-m-d')
        );

        // Get recent expenses
        $recentExpenses = $this->staffModel->getExpenses(
            date('Y-m-d', strtotime('-30 days')),
            date('Y-m-d')
        );

        // Get assigned tasks
        $pendingTasks = $this->staffModel->getAssignedTasks('pending');

        // Get leave balance
        $leaveBalance = $this->staffModel->getLeaveBalance();

        return $this->view('staff/dashboard', [
            'staff' => $staff,
            'todayAttendance' => $todayAttendance,
            'recentExpenses' => $recentExpenses,
            'pendingTasks' => $pendingTasks,
            'leaveBalance' => $leaveBalance,
            'layout' => 'staff'
        ]);
    }

    // Attendance Management
    public function attendance() {
        if (!$this->isAuthenticated() || $_SESSION['user_role'] !== 'staff') {
            return $this->redirect('/login');
        }

        if ($this->isPost()) {
            $data = $this->getPost();
            
            try {
                if ($this->staffModel->recordAttendance($data)) {
                    $_SESSION['success'] = 'Attendance recorded successfully';
                    return $this->redirect('/staff/attendance');
                }
            } catch (\Exception $e) {
                $_SESSION['error'] = 'Failed to record attendance: ' . $e->getMessage();
            }
        }

        // Get monthly attendance
        $monthStart = date('Y-m-01');
        $monthEnd = date('Y-m-t');
        $attendance = $this->staffModel->getAttendance($monthStart, $monthEnd);

        return $this->view('staff/attendance/index', [
            'attendance' => $attendance,
            'layout' => 'staff'
        ]);
    }

    // Clock In/Out
    public function clockInOut() {
        if (!$this->isAuthenticated() || $_SESSION['user_role'] !== 'staff') {
            return $this->json(['error' => 'Unauthorized']);
        }

        $staffId = $_SESSION['user_id'];
        $now = date('Y-m-d H:i:s');
        $today = date('Y-m-d');

        // Check if already clocked in today
        $todayAttendance = $this->staffModel->getAttendance($today, $today);
        
        if (empty($todayAttendance)) {
            // Clock in
            $data = [
                'date' => $today,
                'time_in' => $now,
                'status' => 'present',
                'shift_id' => 1 // Default shift
            ];
            
            if ($this->staffModel->recordAttendance($data)) {
                return $this->json([
                    'success' => true,
                    'message' => 'Clocked in successfully',
                    'time' => $now
                ]);
            }
        } else {
            // Clock out
            $attendance = $todayAttendance[0];
            if (!$attendance['time_out']) {
                $sql = "UPDATE attendance SET time_out = ?, updated_at = NOW() WHERE id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param('si', $now, $attendance['id']);
                
                if ($stmt->execute()) {
                    return $this->json([
                        'success' => true,
                        'message' => 'Clocked out successfully',
                        'time' => $now
                    ]);
                }
            }
        }

        return $this->json(['error' => 'Failed to record attendance']);
    }

    // Expense Management
    public function expenses() {
        if (!$this->isAuthenticated() || $_SESSION['user_role'] !== 'staff') {
            return $this->redirect('/login');
        }

        if ($this->isPost()) {
            $data = $this->getPost();
            
            // Handle file upload
            if (isset($_FILES['receipt'])) {
                $uploadDir = 'uploads/expenses/';
                $fileName = time() . '_' . $_FILES['receipt']['name'];
                $filePath = $uploadDir . $fileName;

                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                if (move_uploaded_file($_FILES['receipt']['tmp_name'], $filePath)) {
                    $data['receipt'] = [
                        'name' => $fileName,
                        'path' => $filePath
                    ];
                }
            }

            try {
                if ($this->staffModel->submitExpense($data)) {
                    $_SESSION['success'] = 'Expense submitted successfully';
                    return $this->redirect('/staff/expenses');
                }
            } catch (\Exception $e) {
                $_SESSION['error'] = 'Failed to submit expense: ' . $e->getMessage();
            }
        }

        // Get expense categories
        $sql = "SELECT * FROM expense_categories WHERE status = 'active'";
        $categories = $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);

        // Get staff expenses
        $expenses = $this->staffModel->getExpenses();

        return $this->view('staff/expenses/index', [
            'categories' => $categories,
            'expenses' => $expenses,
            'layout' => 'staff'
        ]);
    }

    // Leave Management
    public function leave() {
        if (!$this->isAuthenticated() || $_SESSION['user_role'] !== 'staff') {
            return $this->redirect('/login');
        }

        if ($this->isPost()) {
            $data = $this->getPost();
            
            try {
                if ($this->staffModel->applyLeave($data)) {
                    $_SESSION['success'] = 'Leave request submitted successfully';
                    return $this->redirect('/staff/leave');
                }
            } catch (\Exception $e) {
                $_SESSION['error'] = 'Failed to submit leave request: ' . $e->getMessage();
            }
        }

        // Get leave types
        $sql = "SELECT * FROM leave_types WHERE status = 'active'";
        $leaveTypes = $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);

        // Get leave balance
        $leaveBalance = $this->staffModel->getLeaveBalance();

        // Get leave history
        $sql = "SELECT lr.*, lt.name as leave_type 
                FROM leave_requests lr
                JOIN leave_types lt ON lr.leave_type_id = lt.id
                WHERE lr.staff_id = ?
                ORDER BY lr.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $_SESSION['user_id']);
        $stmt->execute();
        $leaveHistory = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        return $this->view('staff/leave/index', [
            'leaveTypes' => $leaveTypes,
            'leaveBalance' => $leaveBalance,
            'leaveHistory' => $leaveHistory,
            'layout' => 'staff'
        ]);
    }

    // Profile Management
    public function profile() {
        if (!$this->isAuthenticated() || $_SESSION['user_role'] !== 'staff') {
            return $this->redirect('/login');
        }

        $staffId = $_SESSION['user_id'];
        $staff = $this->staffModel->find($staffId);

        if ($this->isPost()) {
            $data = $this->getPost();
            $errors = $this->staffModel->validate($data);

            if (empty($errors)) {
                $data['updated_at'] = date('Y-m-d H:i:s');
                
                if ($this->staffModel->update($staffId, $data)) {
                    $_SESSION['success'] = 'Profile updated successfully';
                    return $this->redirect('/staff/profile');
                }
                
                $errors['general'] = 'Failed to update profile';
            }

            return $this->view('staff/profile', [
                'staff' => $staff,
                'errors' => $errors,
                'data' => $data,
                'layout' => 'staff'
            ]);
        }

        return $this->view('staff/profile', [
            'staff' => $staff,
            'layout' => 'staff'
        ]);
    }

    // Change Password
    public function changePassword() {
        if (!$this->isAuthenticated() || $_SESSION['user_role'] !== 'staff') {
            return $this->redirect('/login');
        }

        if ($this->isPost()) {
            $data = $this->getPost();
            
            // Validate current password
            $sql = "SELECT password FROM users WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i', $_SESSION['user_id']);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();

            if (!password_verify($data['current_password'], $user['password'])) {
                return $this->json(['error' => 'Current password is incorrect']);
            }

            // Update password
            $newPassword = password_hash($data['new_password'], PASSWORD_DEFAULT);
            $sql = "UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('si', $newPassword, $_SESSION['user_id']);

            if ($stmt->execute()) {
                return $this->json(['success' => true]);
            }

            return $this->json(['error' => 'Failed to update password']);
        }
    }
}
