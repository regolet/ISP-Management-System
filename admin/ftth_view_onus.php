<?php
require_once '../config.php';
check_auth();

$_SESSION['active_menu'] = 'pon_management';
$page_title = 'View ONUs';

// Get database connection
$conn = get_db_connection();

$napbox_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get NAP box details
$stmt = $conn->prepare("SELECT n.*, o.name as olt_name FROM olt_napboxs n LEFT JOIN olts o ON n.olt_id = o.id WHERE n.id = ?");
$stmt->execute([$napbox_id]);
$napbox = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$napbox) {
    $_SESSION['error'] = "NAP Box not found.";
    header("Location: FTTH.php");
    exit();
}

// Get ONUs connected to this NAP box
$query = "SELECT 
    co.*, 
    c.name as customer_name,
    c.customer_code,
    c.status as customer_status
FROM customer_onus co
LEFT JOIN customers c ON co.customer_id = c.id
WHERE co.napbox_id = ?
ORDER BY co.port_number ASC";

$stmt = $conn->prepare($query);
$stmt->execute([$napbox_id]);
$onus = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'header.php';
include 'navbar.php';
?>

<div class="container-fluid py-4">
    <?php include 'alerts.php'; ?>

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2 mb-0">ONUs Connected to <?php echo htmlspecialchars($napbox['name']); ?></h1>
            <p class="text-muted">Connected to <?php echo htmlspecialchars($napbox['olt_name']); ?> PON <?php echo $napbox['pon_port']; ?></p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="FTTH.php" class="btn btn-secondary d-inline-flex align-items-center gap-2">
                <i class="bx bx-arrow-back"></i>
                <span>Back</span>
            </a>
            <button type="button" class="btn btn-primary d-flex align-items-center gap-2" 
                    data-bs-toggle="modal" data-bs-target="#addOnuModal">
                <i class="bx bx-plus"></i>
                <span>Add ONU</span>
            </button>
        </div>
    </div>

    <!-- ONUs Table -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Port</th>
                            <th>Serial Number</th>
                            <th>Customer</th>
                            <th>Signal Level (dBm)</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($onus) > 0): ?>
                            <?php foreach ($onus as $onu): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($onu['port_number']); ?></td>
                                    <td><?php echo htmlspecialchars($onu['serial_number']); ?></td>
                                    <td>
                                        <?php if ($onu['customer_id']): ?>
                                            <div class="fw-medium"><?php echo htmlspecialchars($onu['customer_name']); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($onu['customer_code']); ?></small>
                                        <?php else: ?>
                                            <span class="text-muted">Not assigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $onu['signal_level'] ? htmlspecialchars($onu['signal_level']) : '-'; ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo match($onu['status']) {
                                                'active' => 'success',
                                                'inactive' => 'secondary',
                                                'fault' => 'danger',
                                                default => 'secondary'
                                            };
                                        ?>">
                                            <?php echo ucfirst($onu['status']); ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group">
                                            <?php if ($onu['customer_id']): ?>
                                                <a href="customer_form.php?id=<?php echo $onu['customer_id']; ?>" 
                                                   class="btn btn-sm btn-info" title="View Customer">
                                                    <i class="bx bx-user"></i>
                                                </a>
                                            <?php endif; ?>
                                            <button type="button" class="btn btn-sm btn-primary" 
                                                    onclick="editOnu(<?php echo htmlspecialchars(json_encode($onu)); ?>)"
                                                    title="Edit ONU">
                                                <i class="bx bx-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger" 
                                                    onclick="deleteOnu(<?php echo $onu['id']; ?>)"
                                                    title="Delete ONU">
                                                <i class="bx bx-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    <i class="bx bx-info-circle fs-4 mb-2"></i>
                                    <p class="mb-0">No ONUs connected</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add ONU Modal -->
<div class="modal fade" id="addOnuModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New ONU</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addOnuForm" method="POST" action="ftth_add_onu.php">
                    <input type="hidden" name="napbox_id" value="<?php echo $napbox_id; ?>">
                    <div class="mb-3">
                        <label for="portNumber" class="form-label">Port Number</label>
                        <input type="number" class="form-control" id="portNumber" name="port_number" required min="1" max="<?php echo $napbox['port_count']; ?>">
                    </div>
                    <div class="mb-3">
                        <label for="serialNumber" class="form-label">Serial Number</label>
                        <input type="text" class="form-control" id="serialNumber" name="serial_number" required>
                    </div>
                    <div class="mb-3">
                        <label for="customerId" class="form-label">Customer</label>
                        <select class="form-select" id="customerId" name="customer_id">
                            <option value="">Select Customer</option>
                            <?php
                            $stmt = $conn->query("SELECT id, name, customer_code FROM customers WHERE status = 'active' ORDER BY name");
                            while ($customer = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                echo "<option value=\"{$customer['id']}\">{$customer['name']} ({$customer['customer_code']})</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="signalLevel" class="form-label">Signal Level (dBm)</label>
                        <input type="number" step="0.01" class="form-control" id="signalLevel" name="signal_level">
                    </div>
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="fault">Fault</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Add ONU</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit ONU Modal -->
<div class="modal fade" id="editOnuModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit ONU</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editOnuForm" method="POST" action="ftth_edit_onu.php">
                    <input type="hidden" name="id" id="editOnuId">
                    <input type="hidden" name="napbox_id" value="<?php echo $napbox_id; ?>">
                    <div class="mb-3">
                        <label for="editPortNumber" class="form-label">Port Number</label>
                        <input type="number" class="form-control" id="editPortNumber" name="port_number" required min="1" max="<?php echo $napbox['port_count']; ?>">
                    </div>
                    <div class="mb-3">
                        <label for="editSerialNumber" class="form-label">Serial Number</label>
                        <input type="text" class="form-control" id="editSerialNumber" name="serial_number" required>
                    </div>
                    <div class="mb-3">
                        <label for="editCustomerId" class="form-label">Customer</label>
                        <select class="form-select" id="editCustomerId" name="customer_id">
                            <option value="">Select Customer</option>
                            <?php
                            $stmt->execute();
                            while ($customer = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                echo "<option value=\"{$customer['id']}\">{$customer['name']} ({$customer['customer_code']})</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="editSignalLevel" class="form-label">Signal Level (dBm)</label>
                        <input type="number" step="0.01" class="form-control" id="editSignalLevel" name="signal_level">
                    </div>
                    <div class="mb-3">
                        <label for="editStatus" class="form-label">Status</label>
                        <select class="form-select" id="editStatus" name="status" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="fault">Fault</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Update ONU</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function editOnu(onu) {
    document.getElementById('editOnuId').value = onu.id;
    document.getElementById('editPortNumber').value = onu.port_number;
    document.getElementById('editSerialNumber').value = onu.serial_number;
    document.getElementById('editCustomerId').value = onu.customer_id || '';
    document.getElementById('editSignalLevel').value = onu.signal_level || '';
    document.getElementById('editStatus').value = onu.status;
    
    new bootstrap.Modal(document.getElementById('editOnuModal')).show();
}

function deleteOnu(id) {
    if (confirm('Are you sure you want to delete this ONU?')) {
        window.location.href = `ftth_delete_onu.php?id=${id}&napbox_id=<?php echo $napbox_id; ?>`;
    }
}
</script>

<?php include 'footer.php'; ?>
