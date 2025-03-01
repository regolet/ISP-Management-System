<?php
require_once '../config.php';
check_auth();

$page_title = 'System Settings';
$_SESSION['active_menu'] = 'settings';

// Get all settings grouped by category
$settings_query = "SELECT * FROM settings ORDER BY category, name";
$settings_result = $conn->query($settings_query);

// Group settings by category
$settings = [];
while ($row = $settings_result->fetch_assoc()) {
    $settings[$row['category']][] = $row;
}

include 'header.php';
include 'navbar.php';
?>

<div class="container-fluid py-4">
    <?php include 'alerts.php'; ?>
    
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2 mb-0">System Settings</h1>
    </div>

    <!-- Settings Form -->
    <div class="card">
        <div class="card-body">
            <form method="POST" action="settings_save.php">
                <div class="accordion" id="settingsAccordion">
                    <?php foreach ($settings as $category => $category_settings): ?>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" 
                                        data-bs-toggle="collapse" 
                                        data-bs-target="#collapse_<?php echo htmlspecialchars($category); ?>">
                                    <i class="bx bx-cog me-2"></i>
                                    <?php echo ucwords(str_replace('_', ' ', $category)); ?>
                                </button>
                            </h2>
                            <div id="collapse_<?php echo htmlspecialchars($category); ?>" 
                                 class="accordion-collapse collapse show" 
                                 data-bs-parent="#settingsAccordion">
                                <div class="accordion-body">
                                    <div class="row g-4">
                                        <?php foreach ($category_settings as $setting): ?>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="form-label">
                                                        <?php echo ucwords(str_replace('_', ' ', $setting['name'])); ?>
                                                    </label>
                                                    
                                                    <?php if ($setting['type'] === 'boolean'): ?>
                                                        <select class="form-select" 
                                                                name="settings[<?php echo $setting['id']; ?>]">
                                                            <option value="1" <?php echo $setting['value'] == '1' ? 'selected' : ''; ?>>
                                                                Enabled
                                                            </option>
                                                            <option value="0" <?php echo $setting['value'] == '0' ? 'selected' : ''; ?>>
                                                                Disabled
                                                            </option>
                                                        </select>
                                                    <?php elseif ($setting['type'] === 'number'): ?>
                                                        <input type="number" class="form-control" 
                                                               name="settings[<?php echo $setting['id']; ?>]"
                                                               value="<?php echo htmlspecialchars($setting['value']); ?>">
                                                    <?php elseif ($setting['type'] === 'textarea'): ?>
                                                        <textarea class="form-control" 
                                                                  name="settings[<?php echo $setting['id']; ?>]"
                                                                  rows="3"><?php echo htmlspecialchars($setting['value']); ?></textarea>
                                                    <?php else: ?>
                                                        <input type="text" class="form-control" 
                                                               name="settings[<?php echo $setting['id']; ?>]"
                                                               value="<?php echo htmlspecialchars($setting['value']); ?>">
                                                    <?php endif; ?>
                                                    
                                                    <?php if (!empty($setting['description'])): ?>
                                                        <div class="form-text">
                                                            <?php echo htmlspecialchars($setting['description']); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary d-flex align-items-center gap-2">
                        <i class="bx bx-save"></i>
                        <span>Save Changes</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.accordion-button:not(.collapsed) {
    background-color: rgba(13, 110, 253, 0.1);
    color: var(--bs-primary);
}
.accordion-button:focus {
    box-shadow: none;
    border-color: rgba(13, 110, 253, 0.1);
}
.accordion-button::after {
    margin-left: auto;
}
.form-text {
    font-size: 0.875em;
    color: #6c757d;
    margin-top: 0.25rem;
}
</style>

<?php include 'footer.php'; ?>
