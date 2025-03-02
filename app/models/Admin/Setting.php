<?php
namespace App\Models\Admin;

use App\Core\Model;

class Setting extends Model 
{
    protected $table = 'settings';
    protected $primaryKey = 'id';
    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'label',
        'description',
        'options',
        'is_public',
        'created_at',
        'updated_at'
    ];

    // Setting types
    const TYPE_TEXT = 'text';
    const TYPE_TEXTAREA = 'textarea';
    const TYPE_NUMBER = 'number';
    const TYPE_EMAIL = 'email';
    const TYPE_SELECT = 'select';
    const TYPE_CHECKBOX = 'checkbox';
    const TYPE_FILE = 'file';
    const TYPE_COLOR = 'color';

    // Setting groups
    const GROUP_GENERAL = 'general';
    const GROUP_COMPANY = 'company';
    const GROUP_EMAIL = 'email';
    const GROUP_BILLING = 'billing';
    const GROUP_SYSTEM = 'system';
    const GROUP_NOTIFICATION = 'notification';

    /**
     * Get settings grouped by group
     */
    public function getGroupedSettings() 
    {
        $sql = "SELECT * FROM {$this->table} ORDER BY `group`, label";
        $settings = $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);

        $grouped = [];
        foreach ($settings as $setting) {
            $grouped[$setting['group']][] = $setting;
        }

        return $grouped;
    }

    /**
     * Get public settings
     */
    public function getPublicSettings() 
    {
        $sql = "SELECT * FROM {$this->table} WHERE is_public = 1";
        return $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get setting value by key
     */
    public function getValue($key, $default = null) 
    {
        $sql = "SELECT value FROM {$this->table} WHERE `key` = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('s', $key);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        return $result ? $result['value'] : $default;
    }

    /**
     * Set setting value by key
     */
    public function setValue($key, $value) 
    {
        $sql = "UPDATE {$this->table} SET value = ? WHERE `key` = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ss', $value, $key);
        return $stmt->execute();
    }

    /**
     * Bulk update settings
     */
    public function bulkUpdate(array $settings) 
    {
        $this->db->begin_transaction();

        try {
            foreach ($settings as $key => $value) {
                $this->setValue($key, $value);
            }
            
            $this->db->commit();
            return true;

        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Create default settings
     */
    public function createDefaultSettings() 
    {
        $defaults = [
            // Company Settings
            [
                'key' => 'company_name',
                'value' => 'ISP Company',
                'type' => self::TYPE_TEXT,
                'group' => self::GROUP_COMPANY,
                'label' => 'Company Name',
                'description' => 'Name of your company',
                'is_public' => 1
            ],
            [
                'key' => 'company_address',
                'value' => '',
                'type' => self::TYPE_TEXTAREA,
                'group' => self::GROUP_COMPANY,
                'label' => 'Company Address',
                'description' => 'Physical address of your company',
                'is_public' => 1
            ],
            
            // Email Settings
            [
                'key' => 'smtp_host',
                'value' => '',
                'type' => self::TYPE_TEXT,
                'group' => self::GROUP_EMAIL,
                'label' => 'SMTP Host',
                'description' => 'SMTP server hostname',
                'is_public' => 0
            ],
            [
                'key' => 'smtp_port',
                'value' => '587',
                'type' => self::TYPE_NUMBER,
                'group' => self::GROUP_EMAIL,
                'label' => 'SMTP Port',
                'description' => 'SMTP server port',
                'is_public' => 0
            ],

            // Billing Settings
            [
                'key' => 'currency',
                'value' => 'PHP',
                'type' => self::TYPE_SELECT,
                'group' => self::GROUP_BILLING,
                'label' => 'Currency',
                'description' => 'Default currency for billing',
                'options' => json_encode(['PHP' => 'Philippine Peso', 'USD' => 'US Dollar']),
                'is_public' => 1
            ],
            [
                'key' => 'tax_rate',
                'value' => '12',
                'type' => self::TYPE_NUMBER,
                'group' => self::GROUP_BILLING,
                'label' => 'Tax Rate (%)',
                'description' => 'Default tax rate for billing',
                'is_public' => 1
            ],

            // System Settings
            [
                'key' => 'maintenance_mode',
                'value' => '0',
                'type' => self::TYPE_CHECKBOX,
                'group' => self::GROUP_SYSTEM,
                'label' => 'Maintenance Mode',
                'description' => 'Put system in maintenance mode',
                'is_public' => 0
            ],
            [
                'key' => 'debug_mode',
                'value' => '0',
                'type' => self::TYPE_CHECKBOX,
                'group' => self::GROUP_SYSTEM,
                'label' => 'Debug Mode',
                'description' => 'Enable debug mode for development',
                'is_public' => 0
            ]
        ];

        $this->db->begin_transaction();

        try {
            foreach ($defaults as $setting) {
                $this->create($setting);
            }
            
            $this->db->commit();
            return true;

        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Validate setting data
     */
    public function validate($data) 
    {
        $errors = [];

        if (empty($data['key'])) {
            $errors['key'] = 'Setting key is required';
        } else {
            // Check key uniqueness
            $sql = "SELECT id FROM {$this->table} WHERE `key` = ? AND id != ?";
            $stmt = $this->db->prepare($sql);
            $id = $data['id'] ?? 0;
            $stmt->bind_param('si', $data['key'], $id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $errors['key'] = 'Setting key must be unique';
            }
        }

        if (empty($data['label'])) {
            $errors['label'] = 'Setting label is required';
        }

        if (empty($data['type'])) {
            $errors['type'] = 'Setting type is required';
        }

        if (empty($data['group'])) {
            $errors['group'] = 'Setting group is required';
        }

        return $errors;
    }
}
