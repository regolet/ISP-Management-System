<?php
namespace App\Models\Admin;

use App\Core\Model;

class Role extends Model 
{
    protected $table = 'roles';
    protected $primaryKey = 'id';
    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_system',
        'created_at',
        'updated_at'
    ];

    /**
     * Get role with permissions
     */
    public function getRoleWithPermissions($id) 
    {
        $sql = "SELECT r.*, GROUP_CONCAT(p.name) as permissions
                FROM {$this->table} r
                LEFT JOIN role_permissions rp ON r.id = rp.role_id
                LEFT JOIN permissions p ON rp.permission_id = p.id
                WHERE r.id = ?
                GROUP BY r.id";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Get all roles with permissions count
     */
    public function getRolesWithPermissionCount() 
    {
        $sql = "SELECT r.*, COUNT(rp.permission_id) as permission_count
                FROM {$this->table} r
                LEFT JOIN role_permissions rp ON r.id = rp.role_id
                GROUP BY r.id
                ORDER BY r.name";

        return $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Assign permissions to role
     */
    public function assignPermissions($roleId, array $permissionIds) 
    {
        $this->db->begin_transaction();

        try {
            // Remove existing permissions
            $sql = "DELETE FROM role_permissions WHERE role_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i', $roleId);
            $stmt->execute();

            // Add new permissions
            if (!empty($permissionIds)) {
                $sql = "INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)";
                $stmt = $this->db->prepare($sql);
                
                foreach ($permissionIds as $permissionId) {
                    $stmt->bind_param('ii', $roleId, $permissionId);
                    $stmt->execute();
                }
            }

            $this->db->commit();
            return true;

        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Check if role has specific permission
     */
    public function hasPermission($roleId, $permissionSlug) 
    {
        $sql = "SELECT 1 FROM role_permissions rp
                JOIN permissions p ON rp.permission_id = p.id
                WHERE rp.role_id = ? AND p.slug = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('is', $roleId, $permissionSlug);
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }

    /**
     * Validate role data
     */
    public function validate($data) 
    {
        $errors = [];

        if (empty($data['name'])) {
            $errors['name'] = 'Role name is required';
        }

        if (empty($data['slug'])) {
            $errors['slug'] = 'Role slug is required';
        } else {
            // Check slug uniqueness
            $sql = "SELECT id FROM {$this->table} WHERE slug = ? AND id != ?";
            $stmt = $this->db->prepare($sql);
            $id = $data['id'] ?? 0;
            $stmt->bind_param('si', $data['slug'], $id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $errors['slug'] = 'Role slug must be unique';
            }
        }

        return $errors;
    }
}
