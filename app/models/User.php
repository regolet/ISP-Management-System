<?php
namespace App\Models;

use App\Core\Model;

class User extends Model 
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $fillable = [
        'username',
        'password',
        'email',
        'role',
        'status',
        'last_login',
        'created_at',
        'updated_at'
    ];

    /**
     * Find user by username
     */
    public function findByUsername($username) 
    {
        $sql = "SELECT u.*, 
                       e.id as employee_id,
                       c.id as customer_id
                FROM {$this->table} u
                LEFT JOIN employees e ON e.user_id = u.id
                LEFT JOIN customers c ON c.user_id = u.id
                WHERE u.username = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('s', $username);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Find user by email
     */
    public function findByEmail($email) 
    {
        $sql = "SELECT * FROM {$this->table} WHERE email = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('s', $email);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Create new user
     */
    public function createUser($data) 
    {
        // Hash password if provided
        if (!empty($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        return $this->create($data);
    }

    /**
     * Update user
     */
    public function updateUser($id, $data) 
    {
        // Hash password if provided
        if (!empty($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        return $this->update($id, $data);
    }

    /**
     * Change user password
     */
    public function changePassword($id, $currentPassword, $newPassword) 
    {
        $user = $this->find($id);
        if (!$user) {
            throw new \Exception('User not found');
        }

        // Verify current password
        if (!password_verify($currentPassword, $user['password'])) {
            throw new \Exception('Current password is incorrect');
        }

        // Update password
        return $this->update($id, [
            'password' => password_hash($newPassword, PASSWORD_DEFAULT)
        ]);
    }

    /**
     * Get user permissions
     */
    public function getUserPermissions($userId) 
    {
        $sql = "SELECT DISTINCT p.* 
                FROM permissions p
                JOIN role_permissions rp ON p.id = rp.permission_id
                JOIN user_roles ur ON rp.role_id = ur.role_id
                WHERE ur.user_id = ?
                ORDER BY p.module, p.name";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Check if user has specific permission
     */
    public function hasPermission($userId, $permissionSlug) 
    {
        $sql = "SELECT 1 
                FROM permissions p
                JOIN role_permissions rp ON p.id = rp.permission_id
                JOIN user_roles ur ON rp.role_id = ur.role_id
                WHERE ur.user_id = ? AND p.slug = ?
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('is', $userId, $permissionSlug);
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }

    /**
     * Validate user data
     */
    public function validate($data, $isNew = true) 
    {
        $errors = [];

        // Required fields
        $required = [
            'username' => 'Username is required',
            'email' => 'Email is required',
            'role' => 'Role is required'
        ];

        if ($isNew) {
            $required['password'] = 'Password is required';
        }

        foreach ($required as $field => $message) {
            if (empty($data[$field])) {
                $errors[$field] = $message;
            }
        }

        // Username validation
        if (!empty($data['username'])) {
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $data['username'])) {
                $errors['username'] = 'Username can only contain letters, numbers and underscores';
            } else {
                // Check uniqueness
                $sql = "SELECT id FROM {$this->table} WHERE username = ? AND id != ?";
                $stmt = $this->db->prepare($sql);
                $id = $data['id'] ?? 0;
                $stmt->bind_param('si', $data['username'], $id);
                $stmt->execute();
                if ($stmt->get_result()->num_rows > 0) {
                    $errors['username'] = 'Username already exists';
                }
            }
        }

        // Email validation
        if (!empty($data['email'])) {
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Invalid email format';
            } else {
                // Check uniqueness
                $sql = "SELECT id FROM {$this->table} WHERE email = ? AND id != ?";
                $stmt = $this->db->prepare($sql);
                $id = $data['id'] ?? 0;
                $stmt->bind_param('si', $data['email'], $id);
                $stmt->execute();
                if ($stmt->get_result()->num_rows > 0) {
                    $errors['email'] = 'Email already exists';
                }
            }
        }

        // Password validation for new users or password changes
        if (!empty($data['password'])) {
            if (strlen($data['password']) < 8) {
                $errors['password'] = 'Password must be at least 8 characters long';
            }
        }

        return $errors;
    }
}
