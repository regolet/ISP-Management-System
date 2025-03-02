<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\Admin\Setting;
use App\Models\Admin\Role;
use App\Models\Admin\Permission;

class SettingsController extends Controller 
{
    private $settingModel;
    private $roleModel;
    private $permissionModel;

    public function __construct() 
    {
        parent::__construct();
        $this->settingModel = new Setting();
        $this->roleModel = new Role();
        $this->permissionModel = new Permission();
    }

    /**
     * Display general settings
     */
    public function general() 
    {
        if (!$this->isAuthenticated()) {
            return $this->redirect('/login');
        }

        if (!$this->permissionModel->userHasPermission($_SESSION['user_id'], 'view_settings')) {
            return $this->redirect('/unauthorized');
        }

        $settings = $this->settingModel->getGroupedSettings();

        return $this->view('admin/settings/general', [
            'settings' => $settings,
            'groups' => [
                Setting::GROUP_GENERAL => 'General Settings',
                Setting::GROUP_COMPANY => 'Company Information',
                Setting::GROUP_EMAIL => 'Email Configuration',
                Setting::GROUP_BILLING => 'Billing Settings',
                Setting::GROUP_SYSTEM => 'System Settings',
                Setting::GROUP_NOTIFICATION => 'Notification Settings'
            ],
            'layout' => 'navbar',
            'title' => 'System Settings'
        ]);
    }

    /**
     * Update settings
     */
    public function update() 
    {
        if (!$this->isAuthenticated()) {
            return $this->redirect('/login');
        }

        if (!$this->permissionModel->userHasPermission($_SESSION['user_id'], 'edit_settings')) {
            return $this->redirect('/unauthorized');
        }

        try {
            $settings = $_POST['settings'] ?? [];
            
            // Handle file uploads
            if (!empty($_FILES['settings']['name'])) {
                foreach ($_FILES['settings']['name'] as $key => $filename) {
                    if (!empty($filename)) {
                        $uploadPath = 'uploads/' . $key . '/';
                        if (!is_dir($uploadPath)) {
                            mkdir($uploadPath, 0777, true);
                        }

                        $targetFile = $uploadPath . basename($filename);
                        if (move_uploaded_file($_FILES['settings']['tmp_name'][$key], $targetFile)) {
                            $settings[$key] = $targetFile;
                        }
                    }
                }
            }

            $this->settingModel->bulkUpdate($settings);
            $_SESSION['success'] = 'Settings updated successfully';

        } catch (\Exception $e) {
            $_SESSION['error'] = 'Failed to update settings: ' . $e->getMessage();
        }

        return $this->redirect('/admin/settings/general');
    }

    /**
     * Display roles and permissions
     */
    public function roles() 
    {
        if (!$this->isAuthenticated()) {
            return $this->redirect('/login');
        }

        if (!$this->permissionModel->userHasPermission($_SESSION['user_id'], 'view_roles')) {
            return $this->redirect('/unauthorized');
        }

        $roles = $this->roleModel->getRolesWithPermissionCount();
        $permissions = $this->permissionModel->getGroupedPermissions();

        return $this->view('admin/settings/roles', [
            'roles' => $roles,
            'permissions' => $permissions,
            'layout' => 'navbar',
            'title' => 'Roles & Permissions'
        ]);
    }

    /**
     * Create new role
     */
    public function createRole() 
    {
        if (!$this->isAuthenticated()) {
            return $this->redirect('/login');
        }

        if (!$this->permissionModel->userHasPermission($_SESSION['user_id'], 'create_roles')) {
            return $this->redirect('/unauthorized');
        }

        if ($this->isPost()) {
            $data = [
                'name' => $_POST['name'],
                'slug' => $_POST['slug'],
                'description' => $_POST['description']
            ];

            $errors = $this->roleModel->validate($data);
            if (empty($errors)) {
                try {
                    $roleId = $this->roleModel->create($data);
                    
                    if (!empty($_POST['permissions'])) {
                        $this->roleModel->assignPermissions($roleId, $_POST['permissions']);
                    }

                    $_SESSION['success'] = 'Role created successfully';
                    return $this->redirect('/admin/settings/roles');

                } catch (\Exception $e) {
                    $errors['general'] = $e->getMessage();
                }
            }

            return $this->view('admin/settings/roles_form', [
                'errors' => $errors,
                'data' => $data,
                'permissions' => $this->permissionModel->getGroupedPermissions(),
                'layout' => 'navbar',
                'title' => 'Create Role'
            ]);
        }

        return $this->view('admin/settings/roles_form', [
            'permissions' => $this->permissionModel->getGroupedPermissions(),
            'layout' => 'navbar',
            'title' => 'Create Role'
        ]);
    }

    /**
     * Update role permissions
     */
    public function updateRolePermissions($id) 
    {
        if (!$this->isAuthenticated()) {
            return $this->redirect('/login');
        }

        if (!$this->permissionModel->userHasPermission($_SESSION['user_id'], 'edit_roles')) {
            return $this->json(['error' => 'Unauthorized'], 403);
        }

        $data = json_decode(file_get_contents('php://input'), true);
        
        try {
            $this->roleModel->assignPermissions($id, $data['permissions'] ?? []);
            return $this->json(['success' => true]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete role
     */
    public function deleteRole($id) 
    {
        if (!$this->isAuthenticated()) {
            return $this->redirect('/login');
        }

        if (!$this->permissionModel->userHasPermission($_SESSION['user_id'], 'delete_roles')) {
            return $this->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $role = $this->roleModel->find($id);
            if ($role['is_system']) {
                throw new \Exception('Cannot delete system roles');
            }

            $this->roleModel->delete($id);
            return $this->json(['success' => true]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }
}
