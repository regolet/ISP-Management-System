<?php
namespace App\Models\Admin;

use App\Core\Model;

class Attendance extends Model {
    protected $table = 'attendance';
    protected $primaryKey = 'id';
    protected $fillable = [
        'employee_id',
        'date',
        'time_in',
        'time_out',
        'status',
        'late_minutes',
        'overtime_minutes',
        'notes',
        'created_at',
        'updated_at'
    ];

    /**
     * Get attendance records with filters
     */
    public function getAttendanceRecords($filters = [], $page = 1, $limit = 10) {
        $offset = ($page - 1) * $limit;
        $where = ['1 = 1'];
        $params = [];
        $types = '';

        if (!empty($filters['date'])) {
            $where[] = "DATE(a.date) = ?";
            $params[] = $filters['date'];
            $types .= 's';
        }

        if (!empty($filters['employee_id'])) {
            $where[] = "a.employee_id = ?";
            $params[] = $filters['employee_id'];
            $types .= 'i';
        }

        if (!empty($filters['status'])) {
            $where[] = "a.status = ?";
            $params[] = $filters['status'];
            $types .= 's';
        }

        if (!empty($filters['department'])) {
            $where[] = "e.department = ?";
            $params[] = $filters['department'];
            $types .= 's';
        }

        $whereClause = implode(' AND ', $where);

        // Get total count for pagination
        $countSql = "SELECT COUNT(*) as total 
                     FROM {$this->table} a
                     JOIN employees e ON a.employee_id = e.id 
                     WHERE {$whereClause}";
        
        $stmt = $this->db->prepare($countSql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $total = $stmt->get_result()->fetch_assoc()['total'];

        // Get attendance records
        $sql = "SELECT a.*, 
                       e.employee_code,
                       e.first_name,
                       e.last_name,
                       e.department,
                       e.position
                FROM {$this->table} a
                JOIN employees e ON a.employee_id = e.id
                WHERE {$whereClause}
                ORDER BY a.date DESC, e.last_name, e.first_name
                LIMIT ? OFFSET ?";

        $stmt = $this->db->prepare($sql);
        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        return [
            'records' => $records,
            'total' => $total,
            'pages' => ceil($total / $limit)
        ];
    }

    /**
     * Get attendance details
     */
    public function getAttendanceDetails($id) {
        $sql = "SELECT a.*, 
                       e.employee_code,
                       e.first_name,
                       e.last_name,
                       e.department,
                       e.position
                FROM {$this->table} a
                JOIN employees e ON a.employee_id = e.id
                WHERE a.id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Record attendance
     */
    public function recordAttendance($data) {
        // Calculate late minutes if time_in is provided
        if (!empty($data['time_in'])) {
            $shiftStart = strtotime('08:00:00'); // Assuming fixed shift start time
            $timeIn = strtotime($data['time_in']);
            $data['late_minutes'] = $timeIn > $shiftStart ? 
                                  floor(($timeIn - $shiftStart) / 60) : 0;
        }

        // Calculate overtime minutes if time_out is provided
        if (!empty($data['time_out'])) {
            $shiftEnd = strtotime('17:00:00'); // Assuming fixed shift end time
            $timeOut = strtotime($data['time_out']);
            $data['overtime_minutes'] = $timeOut > $shiftEnd ? 
                                      floor(($timeOut - $shiftEnd) / 60) : 0;
        }

        // Determine status
        if (!empty($data['time_in']) && !empty($data['time_out'])) {
            $data['status'] = $data['late_minutes'] > 0 ? 'late' : 'present';
        } elseif (!empty($data['time_in'])) {
            $data['status'] = 'present';
        } else {
            $data['status'] = 'absent';
        }

        return $this->create($data);
    }

    /**
     * Update attendance record
     */
    public function updateAttendance($id, $data) {
        $record = $this->find($id);
        if (!$record) {
            throw new \Exception('Attendance record not found');
        }

        // Recalculate late minutes if time_in is updated
        if (isset($data['time_in'])) {
            $shiftStart = strtotime('08:00:00');
            $timeIn = strtotime($data['time_in']);
            $data['late_minutes'] = $timeIn > $shiftStart ? 
                                  floor(($timeIn - $shiftStart) / 60) : 0;
        }

        // Recalculate overtime minutes if time_out is updated
        if (isset($data['time_out'])) {
            $shiftEnd = strtotime('17:00:00');
            $timeOut = strtotime($data['time_out']);
            $data['overtime_minutes'] = $timeOut > $shiftEnd ? 
                                      floor(($timeOut - $shiftEnd) / 60) : 0;
        }

        // Update status if needed
        if (isset($data['time_in']) || isset($data['time_out'])) {
            $timeIn = isset($data['time_in']) ? $data['time_in'] : $record['time_in'];
            $timeOut = isset($data['time_out']) ? $data['time_out'] : $record['time_out'];
            
            if ($timeIn && $timeOut) {
                $data['status'] = $data['late_minutes'] > 0 ? 'late' : 'present';
            } elseif ($timeIn) {
                $data['status'] = 'present';
            } else {
                $data['status'] = 'absent';
            }
        }

        return $this->update($id, $data);
    }

    /**
     * Get monthly attendance summary for employee
     */
    public function getMonthlyAttendanceSummary($employeeId, $month, $year) {
        $sql = "SELECT 
                    COUNT(*) as total_days,
                    COUNT(CASE WHEN status = 'present' THEN 1 END) as present_days,
                    COUNT(CASE WHEN status = 'absent' THEN 1 END) as absent_days,
                    COUNT(CASE WHEN status = 'late' THEN 1 END) as late_days,
                    SUM(late_minutes) as total_late_minutes,
                    SUM(overtime_minutes) as total_overtime_minutes
                FROM {$this->table}
                WHERE employee_id = ?
                AND MONTH(date) = ?
                AND YEAR(date) = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('iii', $employeeId, $month, $year);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Validate attendance data
     */
    public function validate($data) {
        $errors = [];

        if (empty($data['employee_id'])) {
            $errors['employee_id'] = 'Employee is required';
        }

        if (empty($data['date'])) {
            $errors['date'] = 'Date is required';
        }

        if (!empty($data['time_in']) && !empty($data['time_out'])) {
            $timeIn = strtotime($data['time_in']);
            $timeOut = strtotime($data['time_out']);
            
            if ($timeOut <= $timeIn) {
                $errors['time_out'] = 'Time out must be after time in';
            }
        }

        return $errors;
    }
}
