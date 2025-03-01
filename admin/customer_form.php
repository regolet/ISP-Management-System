<?php
require_once '../config.php';
check_auth();

$_SESSION['active_menu'] = 'customers';
$page_title = 'Customer Form';

// Get database connection
$conn = get_db_connection();

// Get customer ID from URL if editing
$customer_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$view_only = isset($_GET['view']) && $_GET['view'] == 1;

// Get plans for dropdown
$plans = $conn->query("SELECT * FROM plans WHERE status = 'active' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Get service areas for dropdown
$service_areas = $conn->query("SELECT * FROM service_areas ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// If editing, get customer data
$customer = null;
if ($customer_id) {
    $stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$customer_id]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$customer) {
        $_SESSION['error'] = "Customer not found.";
        header("Location: customers.php");
        exit();
    }
}

// Helper function to safely get array value
function get_value($array, $key, $default = '') {
    return isset($array[$key]) ? $array[$key] : $default;
}

include 'header.php';
include 'navbar.php';
?>

<div class="container-fluid py-4">
    <?php include 'alerts.php'; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2 mb-0"><?php echo $customer_id ? ($view_only ? 'View Customer' : 'Edit Customer') : 'Add Customer'; ?></h1>
        <a href="customers.php" class="btn btn-secondary btn-back">
            <i class="bx bx-arrow-back"></i> Back to Customers
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="customers_save.php" method="POST" <?php echo $view_only ? 'class="view-only"' : ''; ?>>
                <input type="hidden" name="id" value="<?php echo $customer_id; ?>">
                
                <!-- Basic Information Section -->
                <div class="section-title">Basic Information</div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="customerCode" class="form-label">Customer Code</label>
                        <input type="text" class="form-control" id="customerCode" name="customer_code" 
                               value="<?php echo htmlspecialchars(get_value($customer, 'customer_code')); ?>" 
                               required <?php echo $view_only ? 'disabled' : ''; ?>>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input type="text" class="form-control" id="name" name="name" 
                               value="<?php echo htmlspecialchars(get_value($customer, 'name')); ?>" 
                               required <?php echo $view_only ? 'disabled' : ''; ?>>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="address" class="form-label">Address</label>
                    <textarea class="form-control" id="address" name="address" rows="2" 
                              required <?php echo $view_only ? 'disabled' : ''; ?>><?php echo htmlspecialchars(get_value($customer, 'address')); ?></textarea>
                </div>

                <!-- Contact Information Section -->
                <div class="section-title mt-4">Contact Information</div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="contact" class="form-label">Contact Person</label>
                        <input type="text" class="form-control" id="contact" name="contact" 
                               value="<?php echo htmlspecialchars(get_value($customer, 'contact')); ?>" 
                               <?php echo $view_only ? 'disabled' : ''; ?>>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="contactNumber" class="form-label">Contact Number</label>
                        <input type="text" class="form-control" id="contactNumber" name="contact_number" 
                               value="<?php echo htmlspecialchars(get_value($customer, 'contact_number')); ?>" 
                               <?php echo $view_only ? 'disabled' : ''; ?>>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo htmlspecialchars(get_value($customer, 'email')); ?>" 
                               <?php echo $view_only ? 'disabled' : ''; ?>>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="serviceAreaId" class="form-label">Service Area</label>
                        <select class="form-select" id="serviceAreaId" name="service_area_id" <?php echo $view_only ? 'disabled' : ''; ?>>
                            <option value="">Select Service Area</option>
                            <?php foreach ($service_areas as $area): ?>
                                <option value="<?php echo $area['id']; ?>" 
                                        <?php echo (get_value($customer, 'service_area_id') == $area['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($area['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Service Information Section -->
                <div class="section-title mt-4">Service Information</div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="planId" class="form-label">Plan</label>
                        <select class="form-select" id="planId" name="plan_id" <?php echo $view_only ? 'disabled' : ''; ?>>
                            <option value="">Select Plan</option>
                            <?php foreach ($plans as $plan): ?>
                                <option value="<?php echo $plan['id']; ?>" 
                                        <?php echo (get_value($customer, 'plan_id') == $plan['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($plan['name']); ?> 
                                    (₱<?php echo number_format($plan['amount'], 2); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="status" class="form-label">Status</label>
                        <div class="pt-2">
                            <span class="status-badge <?php echo get_value($customer, 'status', 'inactive'); ?>">
                                <?php echo ucfirst(get_value($customer, 'status', 'inactive')); ?>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="installationDate" class="form-label">Installation Date</label>
                        <input type="date" class="form-control" id="installationDate" name="installation_date" 
                               value="<?php echo get_value($customer, 'installation_date'); ?>" 
                               <?php echo $view_only ? 'disabled' : ''; ?>>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="dueDate" class="form-label">Due Date</label>
                        <input type="date" class="form-control" id="dueDate" name="due_date" 
                               value="<?php echo get_value($customer, 'due_date'); ?>" 
                               <?php echo $view_only ? 'disabled' : ''; ?>>
                    </div>
                </div>

                <!-- Financial Information Section -->
                <div class="section-title mt-4">Financial Information</div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="installationFee" class="form-label">Installation Fee</label>
                        <div class="amount-display">
                            ₱<?php echo number_format(get_value($customer, 'installation_fee', '0.00'), 2); ?>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="balance" class="form-label">Balance</label>
                        <div class="amount-display">
                            ₱<?php echo number_format(get_value($customer, 'balance', '0.00'), 2); ?>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="creditBalance" class="form-label">Credit Balance</label>
                        <div class="amount-display">
                            ₱<?php echo number_format(get_value($customer, 'credit_balance', '0.00'), 2); ?>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="outstandingBalance" class="form-label">Outstanding Balance</label>
                        <div class="amount-display">
                            ₱<?php echo number_format(get_value($customer, 'outstanding_balance', '0.00'), 2); ?>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="installationNotes" class="form-label">Installation Notes</label>
                    <textarea class="form-control" id="installationNotes" name="installation_notes" rows="3" 
                              <?php echo $view_only ? 'disabled' : ''; ?>><?php echo htmlspecialchars(get_value($customer, 'installation_notes')); ?></textarea>
                </div>

                <?php if (!$view_only): ?>
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <?php echo $customer_id ? 'Update Customer' : 'Add Customer'; ?>
                        </button>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <?php if ($customer_id): ?>
        <!-- Customer ONUs Section -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0">Connected ONUs</h5>
            </div>
            <div class="card-body">
                <?php
                $stmt = $conn->prepare("
                    SELECT co.*, n.name as napbox_name, o.name as olt_name 
                    FROM customer_onus co
                    LEFT JOIN olt_napboxs n ON co.napbox_id = n.id
                    LEFT JOIN olts o ON n.olt_id = o.id
                    WHERE co.customer_id = ?
                    ORDER BY o.name, n.name, co.port_number
                ");
                $stmt->execute([$customer_id]);
                $onus = $stmt->fetchAll(PDO::FETCH_ASSOC);
                ?>

                <?php if (count($onus) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>OLT</th>
                                    <th>NAP Box</th>
                                    <th>Port</th>
                                    <th>Serial Number</th>
                                    <th>Signal Level</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($onus as $onu): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars(get_value($onu, 'olt_name', 'N/A')); ?></td>
                                        <td><?php echo htmlspecialchars(get_value($onu, 'napbox_name', 'N/A')); ?></td>
                                        <td><?php echo htmlspecialchars(get_value($onu, 'port_number')); ?></td>
                                        <td><?php echo htmlspecialchars(get_value($onu, 'serial_number')); ?></td>
                                        <td>
                                            <?php if (get_value($onu, 'signal_level')): ?>
                                                <span class="signal-level"><?php echo htmlspecialchars(get_value($onu, 'signal_level')); ?> dBm</span>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="onu-status <?php echo get_value($onu, 'status', 'inactive'); ?>">
                                                <?php echo ucfirst(get_value($onu, 'status', 'inactive')); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-0">No ONUs connected to this customer.</p>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
/* View mode styling */
form.view-only {
    padding: 1rem;
}

form.view-only .form-control:disabled,
form.view-only .form-select:disabled {
    background-color: transparent;
    opacity: 1;
    padding: 0.375rem 0;
    border: none;
    font-size: 1rem;
    color: #333;
    min-height: unset;
}

form.view-only .form-label {
    font-weight: 600;
    margin-bottom: 0.25rem;
    color: #666;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Card styling */
.card {
    border: none;
    box-shadow: 0 0 20px rgba(0,0,0,0.05);
    border-radius: 10px;
    margin-bottom: 2rem;
}

.card-header {
    background-color: transparent;
    border-bottom: 1px solid rgba(0,0,0,0.05);
    padding: 1.5rem;
}

.card-body {
    padding: 1.5rem;
}

/* Section styling */
.section-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: #333;
    margin-bottom: 1.5rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #f0f0f0;
}

/* Balance amounts */
.amount-display {
    font-family: 'Roboto Mono', monospace;
    font-size: 1.1rem;
    color: #2c3e50;
}

/* Status badge */
.status-badge {
    padding: 0.5rem 1rem;
    border-radius: 50px;
    font-weight: 500;
    font-size: 0.875rem;
    text-transform: capitalize;
    display: inline-block;
}

.status-badge.active {
    background-color: #e8f5e9;
    color: #2e7d32;
}

.status-badge.inactive {
    background-color: #f5f5f5;
    color: #616161;
}

.status-badge.suspended {
    background-color: #fbe9e7;
    color: #d84315;
}

/* Table styling */
.table {
    margin-bottom: 0;
}

.table thead th {
    background-color: #f8f9fa;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.5px;
    padding: 1rem;
    border-top: none;
}

.table tbody td {
    padding: 1rem;
    vertical-align: middle;
}

.table-hover tbody tr:hover {
    background-color: #f8f9fa;
}

/* Back button */
.btn-back {
    padding: 0.5rem 1rem;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 500;
    border-radius: 6px;
}

.btn-back i {
    font-size: 1.25rem;
}

/* Signal level and status badges in ONU table */
.signal-level {
    font-family: 'Roboto Mono', monospace;
    padding: 0.25rem 0.5rem;
    background-color: #f0f7ff;
    border-radius: 4px;
    color: #0066cc;
    font-size: 0.875rem;
}

.onu-status {
    padding: 0.25rem 0.75rem;
    border-radius: 50px;
    font-size: 0.75rem;
    font-weight: 500;
}

.onu-status.active {
    background-color: #e8f5e9;
    color: #2e7d32;
}

.onu-status.inactive {
    background-color: #f5f5f5;
    color: #616161;
}

.onu-status.fault {
    background-color: #fbe9e7;
    color: #d84315;
}
</style>

<?php include 'footer.php'; ?>
