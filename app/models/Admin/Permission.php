<?php
namespace App\Models\Admin;

use App\Core\Model;

class Permission extends Model 
{
    protected $table = 'permissions';
    protected $primaryKey = 'id';
    protected $fillable = [
        'name',
        'slug',
        'description',
        'module',
        'created_at',
        'updated_at'
    ];

    /**
     * Get permissions grouped by module
     */
    public function getGroupedPermissions() 
    {
        $sql = "SELECT * FROM {$this->table} ORDER BY module, name";
        $permissions = $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);

        $grouped = [];
        foreach ($permissions as $permission) {
            $grouped[$permission['module']][] = $permission;
        }

        return $grouped;
    }

    /**
     * Get permissions for a role
     */
    public function getRolePermissions($roleId) 
    {
        $sql = "SELECT p.* 
                FROM {$this->table} p
                JOIN role_permissions rp ON p.id = rp.permission_id
                WHERE rp.role_id = ?
                ORDER BY p.module, p.name";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $roleId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get permissions for a user
     */
    public function getUserPermissions($userId) 
    {
        $sql = "SELECT DISTINCT p.* 
                FROM {$this->table} p
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
    public function userHasPermission($userId, $permissionSlug) 
    {
        $sql = "SELECT 1 
                FROM {$this->table} p
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
     * Create default permissions for a module
     */
    public function createDefaultPermissions($module) 
    {
        $actions = ['view', 'create', 'edit', 'delete'];
        $permissions = [];

        foreach ($actions as $action) {
            $permissions[] = [
                'name' => ucfirst($action) . ' ' . ucfirst($module),
                'slug' => $action . '_' . strtolower($module),
                'description' => 'Can ' . $action . ' ' . strtolower($module),
                'module' => ucfirst($module)
            ];
        }

        $this->db->begin_transaction();

        try {
            foreach ($permissions as $permission) {
                $this->create($permission);
            }
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Validate permission data
     */
    public function validate($data) 
    {
        $errors = [];

        if (empty($data['name'])) {
            $errors['name'] = 'Permission name is required';
        }

        if (empty($data['slug'])) {
            $errors['slug'] = 'Permission slug is required';
        } else {
            // Check slug uniqueness
            $sql = "SELECT id FROM {$this->table} WHERE slug = ? AND id != ?";
            $stmt = $this->db->prepare($sql);
            $id = $data['id'] ?? 0;
            $stmt->bind_param('si', $data['slug'], $id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $errors['slug'] = 'Permission slug must be unique';
            }
        }

        if (empty($data['module'])) {
            $errors['module'] = 'Module name is required';
        }

        return $errors;
    }

    /**
     * Get available modules
     */
    public function getModules() 
    {
        $sql = "SELECT DISTINCT module FROM {$this->table} ORDER BY module";
        $result = $this->db->query($sql);
        return array_column($result->fetch_all(MYSQLI_ASSOC), 'module');
    }
}
