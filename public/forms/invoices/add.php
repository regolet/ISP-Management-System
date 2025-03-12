<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Invoice - ISP Management System</title>
    <!-- Core CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="/assets/css/main.css" rel="stylesheet">
    <link href="/assets/css/dashboard.css" rel="stylesheet">
</head>
<body>
    <div class="dashboard-container">
        <?php
        require_once '../../../app/init.php';
        require_once '../../../app/Models/Invoice.php';
        require_once '../../../app/Controllers/ClientController.php';
        require_once '../../../app/Controllers/InvoiceController.php';
        require_once '../../../views/layouts/sidebar.php';

        use App\Controllers\ClientController;
        use App\Controllers\InvoiceController;

        // Database connection
        $db = new PDO("sqlite:../../../database/isp-management.sqlite");
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Initialize Client Controller
        $clientController = new ClientController($db);

        // Initialize Invoice Controller
        $invoiceController = new InvoiceController($db);

        // Get all clients
        $clients = $clientController->getClients()['clients'];

        // Generate CSRF token
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        $csrf_token = $_SESSION['csrf_token'];

        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validate CSRF token
            if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $csrf_token) {
                add_notification('Invalid CSRF token', 'error');
            } else {

                // Get client ID from the form
                $client_id = isset($_POST['client_id']) ? (int)$_POST['client_id'] : 0;

                // Get client data
                $client = $clientController->getClientById($client_id);

                // Generate invoice data
                $invoice_number = 'INV-' . date('YmdHis');
                $total_amount = 0;
                $due_date = date('Y-m-d', strtotime('+30 days'));
                $status = 'unpaid';

                // Get billing items from the form
                $item_descriptions = $_POST['item_description'] ?? [];
                $item_quantities = $_POST['item_quantity'] ?? [];
                $item_prices = $_POST['item_price'] ?? [];

                // Calculate total amount and store billing items
                $billing_items = [];
                for ($i = 0; $i < count($item_descriptions); $i++) {
                    $description = $item_descriptions[$i];
                    $quantity = (float)$item_quantities[$i];
                    $price = (float)$item_prices[$i];
                    $total = $quantity * $price;
                    $total_amount += $total;

                    $billing_items[] = [
                        'description' => $description,
                        'quantity' => $quantity,
                        'price' => $price,
                        'total' => $total
                    ];
                }

                // Create invoice
                if ($invoiceController->createInvoice($client_id, $invoice_number, $total_amount, $due_date, $status, $billing_items)) {
                    // Redirect to invoices page with success message
                    add_notification('Invoice generated successfully', 'success');
                    header("Location: /invoices.php?message=Invoice generated successfully");
                    exit();
                } else {
                    // Redirect to invoices page with error message
                    add_notification('Failed to generate invoice', 'error');
                    header("Location: /invoices.php?error=Failed to generate invoice");
                    exit();
                }
            }
        }

        ?>
        <?php renderSidebar('invoices'); ?>
        <div class="main-content p-4">
            <h2>Generate Invoice</h2>
            <?php if (!empty($GLOBALS['notifications'])): ?>
                <?php foreach ($GLOBALS['notifications'] as $notification): ?>
                    <div class="alert alert-<?php echo htmlspecialchars($notification['type']); ?>">
                        <?php echo htmlspecialchars($notification['message']); ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            <form action="" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <div class="mb-3">
                    <label for="client_id" class="form-label">Client</label>
                    <select class="form-select" id="client_id" name="client_id" required onchange="updateBillingItems(this)">
                        <option value="">Select Client</option>
                        <?php foreach ($clients as $client): ?>
                            <option value="<?php echo htmlspecialchars($client['id']); ?>" data-plan-name="<?php echo htmlspecialchars($client['plan_name'] ?? ''); ?>" data-plan-price="<?php echo htmlspecialchars($client['plan_price'] ?? 0); ?>">
                                <?php echo htmlspecialchars($client['first_name'] . ' ' . $client['last_name'] . ' (' . $client['client_number'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Billing Items Form -->
                <div class="table-responsive">
                    <table class="table table-bordered" id="billing-items">
                        <thead>
                            <tr>
                                <th>Description</th>
                                <th>Quantity</th>
                                <th>Price</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            
                        </tbody>
                    </table>
                </div>
                <button type="button" class="btn btn-secondary" onclick="addBillingItem()">Add Billing Item</button>

                <button type="submit" class="btn btn-primary">Generate Invoice</button>
                <a href="/invoices.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function addBillingItem() {
            const billingItemsTable = document.getElementById('billing-items').getElementsByTagName('tbody')[0];
            const newRow = billingItemsTable.insertRow();
            newRow.classList.add('billing-item');
            newRow.innerHTML = `
                <td><input type="text" class="form-control" name="item_description[]" required></td>
                <td><input type="number" class="form-control" name="item_quantity[]" value="1" required></td>
                <td><input type="number" class="form-control" name="item_price[]" value="0.00" step="0.01" required></td>
                <td><button type="button" class="btn btn-danger btn-sm" onclick="removeBillingItem(this)">Remove</button></td>
            `;
        }

        function removeBillingItem(button) {
            const row = button.parentNode.parentNode;
            row.parentNode.removeChild(row);
        }

        function updateBillingItems(selectElement) {
            const clientSelect = document.getElementById('client_id');
            const selectedOption = clientSelect.options[clientSelect.selectedIndex];
            const planName = selectedOption.dataset.planName;
            const planPrice = selectedOption.dataset.planPrice;
            console.log("Selected option:", selectedOption);
            console.log("Plan Name:", planName);
            console.log("Plan Price:", planPrice);
            const billingItemsTable = document.getElementById('billing-items').getElementsByTagName('tbody')[0];
            billingItemsTable.innerHTML = ''; // Clear existing rows

            let today = new Date();
            let dd = String(today.getDate()).padStart(2, '0');
            let mm = String(today.getMonth() + 1).padStart(2, '0'); //January is 0!
            let yyyy = today.getFullYear();

            const monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun",
              "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"
            ];
            let fromDate = monthNames[today.getMonth()] + "-" + dd + "-" + yyyy;

            let nextMonth = new Date(today.getFullYear(), today.getMonth() + 1, today.getDate());
            let nextMonthDd = String(nextMonth.getDate()).padStart(2, '0');
            let nextMonthMm = String(nextMonth.getMonth());
            let nextMonthYyyy = nextMonth.getFullYear();
            let toDate = monthNames[nextMonthMm] + "-" + nextMonthDd + "-" + nextMonthYyyy;

            let newRow = billingItemsTable.insertRow();
            newRow.classList.add('billing-item');
            newRow.innerHTML = `
                <td><input type="text" class="form-control" name="item_description[]" value="${planName} [${fromDate} - ${toDate}]" required></td>
                <td><input type="number" class="form-control" name="item_quantity[]" value="1" required></td>
                <td><input type="number" class="form-control" name="item_price[]" value="${planPrice}" step="0.01" required></td>
                <td><button type="button" class="btn btn-danger btn-sm" onclick="removeBillingItem(this)">Remove</button></td>
            `;
        }
    </script>
</body>
</html>