<?php
namespace App\Models\Staff;

use App\Core\Model;

class Attendance extends Model {
    protected $table = 'staff_attendance';
    protected $primaryKey = 'id';
    protected $fillable = [
        'staff_id',
        'date',
        'time_in',
        'time_out',
        'shift_id',
        'status',
        'late_minutes',
        'overtime_minutes',
        'notes',
        'created_at',
        'updated_at'
    ];

    /**
     * Get staff attendance records
     */
    public function getAttendanceRecords($staffId, $startDate = null, $endDate = null) {
        $sql = "SELECT a.*,
                       s.name as shift_name,
                       s.start_time as shift_start,
                       s.end_time as shift_end
                FROM {$this->table} a
                LEFT JOIN shifts s ON a.shift_id = s.id
                WHERE a.staff_id = ?";
        
        $params = [$staffId];
        $types = 'i';

        if ($startDate && $endDate) {
            $sql .= " AND a.date BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
            $types .= 'ss';
        }

        $sql .= " ORDER BY a.date DESC, a.time_in ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get today's attendance
     */
    public function getTodayAttendance($staffId) {
        $sql = "SELECT a.*,
                       s.name as shift_name,
                       s.start_time as shift_start,
                       s.end_time as shift_end
                FROM {$this->table} a
                LEFT JOIN shifts s ON a.shift_id = s.id
                WHERE a.staff_id = ? AND a.date = CURRENT_DATE()";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $staffId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Record time action (in/out)
     */
    public function recordTimeAction($staffId, $action) {
        $now = date('Y-m-d H:i:s');
        $today = date('Y-m-d');

        // Get current shift
        $sql = "SELECT * FROM shifts WHERE ? BETWEEN start_time AND end_time";
        $stmt = $this->db->prepare($sql);
        $currentTime = date('H:i:s');
        $stmt->bind_param('s', $currentTime);
        $stmt->execute();
        $shift = $stmt->get_result()->fetch_assoc();

        // Begin transaction
        $this->db->getConnection()->begin_transaction();

        try {
            if ($action === 'in') {
                // Check if already clocked in
                $sql = "SELECT id FROM {$this->table} 
                        WHERE staff_id = ? AND date = CURRENT_DATE()";
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param('i', $staffId);
                $stmt->execute();
                if ($stmt->get_result()->num_rows > 0) {
                    throw new \Exception('Already clocked in for today');
                }

                // Calculate late minutes
                $lateMinutes = 0;
                if ($shift) {
                    $shiftStart = strtotime($today . ' ' . $shift['start_time']);
                    $timeIn = strtotime($now);
                    if ($timeIn > $shiftStart) {
                        $lateMinutes = round(($timeIn - $shiftStart) / 60);
                    }
                }

                // Insert new attendance record
                $sql = "INSERT INTO {$this->table} (
                            staff_id,
                            date,
                            time_in,
                            shift_id,
                            status,
                            late_minutes,
                            created_at
                        ) VALUES (?, CURRENT_DATE(), ?, ?, ?, ?, NOW())";
                
                $stmt = $this->db->prepare($sql);
                $status = $lateMinutes > 0 ? 'late' : 'present';
                $stmt->bind_param('isisi',
                    $staffId,
                    $now,
                    $shift['id'] ?? null,
                    $status,
                    $lateMinutes
                );
                $stmt->execute();

            } else { // Clock out
                // Get current attendance record
                $sql = "SELECT * FROM {$this->table} 
                        WHERE staff_id = ? AND date = CURRENT_DATE()";
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param('i', $staffId);
                $stmt->execute();
                $attendance = $stmt->get_result()->fetch_assoc();

                if (!$attendance) {
                    throw new \Exception('No clock-in record found for today');
                }

                if ($attendance['time_out']) {
                    throw new \Exception('Already clocked out for today');
                }

                // Calculate overtime minutes
                $overtimeMinutes = 0;
                if ($shift) {
                    $shiftEnd = strtotime($today . ' ' . $shift['end_time']);
                    $timeOut = strtotime($now);
                    if ($timeOut > $shiftEnd) {
                        $overtimeMinutes = round(($timeOut - $shiftEnd) / 60);
                    }
                }

                // Update attendance record
                $sql = "UPDATE {$this->table} 
                        SET time_out = ?,
                            overtime_minutes = ?,
                            updated_at = NOW()
                        WHERE id = ?";
                
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param('sii',
                    $now,
                    $overtimeMinutes,
                    $attendance['id']
                );
                $stmt->execute();
            }

            $this->db->getConnection()->commit();
            return true;

        } catch (\Exception $e) {
            $this->db->getConnection()->rollback();
            throw $e;
        }
    }

    /**
     * Get attendance summary
     */
    public function getAttendanceSummary($staffId, $startDate, $endDate) {
        $sql = "SELECT 
                    COUNT(*) as total_days,
                    COUNT(CASE WHEN status = 'present' THEN 1 END) as present_days,
                    COUNT(CASE WHEN status = 'late' THEN 1 END) as late_days,
                    COUNT(CASE WHEN status = 'absent' THEN 1 END) as absent_days,
                    SUM(late_minutes) as total_late_minutes,
                    SUM(overtime_minutes) as total_overtime_minutes
                FROM {$this->table}
                WHERE staff_id = ?
                AND date BETWEEN ? AND ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('iss', $staffId, $startDate, $endDate);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Get monthly attendance data
     */
    public function getMonthlyAttendance($staffId, $year, $month) {
        $sql = "SELECT date, status, late_minutes, overtime_minutes
                FROM {$this->table}
                WHERE staff_id = ?
                AND YEAR(date) = ?
                AND MONTH(date) = ?
                ORDER BY date ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('iii', $staffId, $year, $month);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get staff shifts
     */
    public function getShifts() {
        $sql = "SELECT * FROM shifts WHERE status = 'active' ORDER BY start_time";
        return $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Validate attendance data
     */
    public function validate($data) {
        $errors = [];

        if (empty($data['date']) || !strtotime($data['date'])) {
            $errors['date'] = 'Valid date is required';
        }

        if (!empty($data['time_in']) && !strtotime($data['time_in'])) {
            $errors['time_in'] = 'Valid time in is required';
        }

        if (!empty($data['time_out'])) {
            if (!strtotime($data['time_out'])) {
                $errors['time_out'] = 'Valid time out is required';
            } elseif (strtotime($data['time_out']) <= strtotime($data['time_in'])) {
                $errors['time_out'] = 'Time out must be after time in';
            }
        }

        return $errors;
    }
}
