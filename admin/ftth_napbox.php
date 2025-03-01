<?php
require_once '../config.php';
check_auth();

$page_title = 'NAP Box Management';
$_SESSION['active_menu'] = 'pon_management';
include 'header.php';
include 'navbar.php';
?>

<div class="container-fluid py-4">
    <?php include 'alerts.php'; ?>
    
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">NAP Box List</h5>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addNapboxModal">
                <i class="bx bx-plus"></i> Add NAP Box
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Connected To</th>
                            <th>Available Ports</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="napboxTableBody">
                        <tr>
                            <td colspan="4" class="text-center">Loading...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add NAP Box Modal -->
<div class="modal fade" id="addNapboxModal" tabindex="-1" aria-labelledby="addNapboxModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addNapboxModalLabel">Add New NAP Box</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addNapboxForm" method="POST" action="ftth_add_napbox.php">
                    <div class="mb-3">
                        <label for="napboxName" class="form-label">NAP Box Name</label>
                        <input type="text" class="form-control" id="napboxName" name="napboxName" required>
                    </div>
                    <div class="mb-3">
                        <label for="connectionType" class="form-label">Connect To</label>
                        <select class="form-select" id="connectionType" name="connectionType" required>
                            <option value="">Select Type</option>
                            <option value="OLT">OLT</option>
                            <option value="LCP">LCP</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="connectionId" class="form-label">Select Connection</label>
                        <select class="form-select" id="connectionId" name="connectionId" required>
                            <option value="">Select Type First</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="portCount" class="form-label">Available Ports</label>
                        <input type="number" class="form-control" id="portCount" name="port_count" required min="1">
                        <small class="text-muted">Number of ports this NAP box will have for customer connections</small>
                    </div>
                    <div class="text-end">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Add NAP Box</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit NAP Box Modal -->
<div class="modal fade" id="editNapboxModal" tabindex="-1" aria-labelledby="editNapboxModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editNapboxModalLabel">Edit NAP Box</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editNapboxForm" method="POST" action="ftth_edit_napbox.php">
                    <input type="hidden" id="editNapboxId" name="id">
                    <div class="mb-3">
                        <label for="editNapboxName" class="form-label">NAP Box Name</label>
                        <input type="text" class="form-control" id="editNapboxName" name="napboxName" required>
                    </div>
                    <div class="mb-3">
                        <label for="editConnectionType" class="form-label">Connect To</label>
                        <select class="form-select" id="editConnectionType" name="connectionType" required>
                            <option value="">Select Type</option>
                            <option value="OLT">OLT</option>
                            <option value="LCP">LCP</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="editConnectionId" class="form-label">Select Connection</label>
                        <select class="form-select" id="editConnectionId" name="connectionId" required>
                            <option value="">Select Type First</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="editPortCount" class="form-label">Available Ports</label>
                        <input type="number" class="form-control" id="editPortCount" name="port_count" required min="1">
                        <small class="text-muted">Number of ports this NAP box will have for customer connections</small>
                    </div>
                    <div class="text-end">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Load JavaScript -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/ftth_napbox.js"></script>

<?php include 'footer.php'; ?>
