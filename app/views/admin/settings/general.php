<?php
$title = 'General Settings - Admin Panel';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2><i class='bx bx-cog'></i> System Settings</h2>
    </div>
</div>

<div class="row">
    <div class="col-md-3">
        <!-- Settings Navigation -->
        <div class="card mb-4">
            <div class="list-group list-group-flush">
                <a href="/admin/settings/general" class="list-group-item list-group-item-action active">
                    <i class='bx bx-cog'></i> General Settings
                </a>
                <a href="/admin/settings/roles" class="list-group-item list-group-item-action">
                    <i class='bx bx-shield'></i> Roles & Permissions
                </a>
                <a href="/admin/backup" class="list-group-item list-group-item-action">
                    <i class='bx bx-data'></i> Backup Management
                </a>
                <a href="/admin/audit" class="list-group-item list-group-item-action">
                    <i class='bx bx-history'></i> Audit Logs
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-9">
        <form method="POST" action="/admin/settings/update" enctype="multipart/form-data">
            <!-- Company Settings -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class='bx bx-building'></i> Company Information</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Company Name</label>
                            <input type="text" class="form-control" name="settings[company_name]" 
                                   value="<?= htmlspecialchars($settings['company_name'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Company Logo</label>
                            <input type="file" class="form-control" name="settings[company_logo]">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Company Address</label>
                        <textarea class="form-control" name="settings[company_address]" rows="2"><?= htmlspecialchars($settings['company_address'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Email Settings -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class='bx bx-envelope'></i> Email Configuration</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">SMTP Host</label>
                            <input type="text" class="form-control" name="settings[smtp_host]" 
                                   value="<?= htmlspecialchars($settings['smtp_host'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">SMTP Port</label>
                            <input type="number" class="form-control" name="settings[smtp_port]" 
                                   value="<?= htmlspecialchars($settings['smtp_port'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">SMTP Username</label>
                            <input type="text" class="form-control" name="settings[smtp_username]" 
                                   value="<?= htmlspecialchars($settings['smtp_username'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">SMTP Password</label>
                            <input type="password" class="form-control" name="settings[smtp_password]" 
                                   value="<?= htmlspecialchars($settings['smtp_password'] ?? '') ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Billing Settings -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class='bx bx-money'></i> Billing Settings</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Currency</label>
                            <select class="form-select" name="settings[currency]">
                                <option value="PHP" <?= ($settings['currency'] ?? '') === 'PHP' ? 'selected' : '' ?>>Philippine Peso (₱)</option>
                                <option value="USD" <?= ($settings['currency'] ?? '') === 'USD' ? 'selected' : '' ?>>US Dollar ($)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tax Rate (%)</label>
                            <input type="number" class="form-control" name="settings[tax_rate]" 
                                   value="<?= htmlspecialchars($settings['tax_rate'] ?? '') ?>" step="0.01">
                        </div>
                    </div>
                </div>
            </div>

            <!-- System Settings -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class='bx bx-cog'></i> System Settings</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input type="checkbox" class="form-check-input" name="settings[maintenance_mode]" 
                                       value="1" <?= ($settings['maintenance_mode'] ?? '') == '1' ? 'checked' : '' ?>>
                                <label class="form-check-label">Maintenance Mode</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input type="checkbox" class="form-check-input" name="settings[debug_mode]" 
                                       value="1" <?= ($settings['debug_mode'] ?? '') == '1' ? 'checked' : '' ?>>
                                <label class="form-check-label">Debug Mode</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-end">
                <button type="submit" class="btn btn-primary">
                    <i class='bx bx-save'></i> Save Settings
                </button>
            </div>
        </form>
    </div>
</div>
