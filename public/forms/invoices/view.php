<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Billing Invoice - ISP Management System</title>
    <!-- Core CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="/assets/css/main.css" rel="stylesheet">
    <link href="/assets/css/dashboard.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f8f9fa;
        }
        .invoice-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 30px;
            border: 1px solid #ddd;
            background-color: #fff;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }
        .invoice-header {
            text-align: left;
            margin-bottom: 25px;
            border-bottom: 2px solid #eee;
            padding-bottom: 20px;
            position: relative;
        }
        .invoice-header h2 {
            color: #333;
            margin-top: 0;
        }
        .header-right {
            position: absolute;
            top: 0;
            right: 0;
            text-align: right;
        }
        .company-logo {
            text-align: center;
            margin-bottom: 20px;
        }
        .company-logo img {
            max-width: 150px;
            height: auto;
        }
        .bill-to, .bill-from {
            margin-bottom: 20px;
        }
        .bill-to strong, .bill-from strong {
            color: #555;
        }
        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
        }
        .invoice-table th, .invoice-table td {
            border: 1px solid #ddd;
            padding: 12px 15px;
            text-align: left;
        }
        .invoice-table th {
            background-color: #f2f2f2;
            color: #333;
        }
        .invoice-table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .invoice-total {
            text-align: right;
            font-size: 1.2em;
            color: #333;
        }
        .total-amount {
            font-size: 1.5em;
            color: #007bff;
        }
        .total-amount-label {
            font-size: 1em;
            font-weight: bold;
        }
        .invoice-status {
            margin-top: 15px;
            padding: 10px;
            text-align: center;
            border-radius: 5px;
        }
        .status-paid {
            background-color: #d4edda;
            color: #155724;
        }
        .status-unpaid {
            background-color: #f8d7da;
            color: #721c24;
        }
        .status-overdue {
            background-color: #fff3cd;
            color: #856404;
        }
        .btn-container {
            margin-top: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php
        require_once '../../../app/init.php';
        require_once '../../../app/Controllers/InvoiceController.php';
        require_once '../../../app/Models/Invoice.php'; // Add this line
        require_once '../../../views/layouts/sidebar.php';

        use App\Controllers\InvoiceController;

        // Database connection
        $db = new PDO("sqlite:../../../database/isp-management.sqlite");
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Initialize Invoice Controller
        $invoiceController = new InvoiceController($db);

        // Get invoice ID from the query string
        $invoice_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

        // Get invoice data
        $invoice = $invoiceController->getInvoiceById($invoice_id);

        // If invoice not found, redirect to invoices page
        if (!$invoice) {
            header("Location: /invoices.php");
            exit();
        }

        // Function to format date as mmm-dd-yyyy
        function formatDate($date) {
            return date("M-d-Y", strtotime($date));
        }

        // Get the invoice's created date
        $invoice_date = formatDate($invoice['created_at']);
        $due_date = formatDate($invoice['due_date']);

        // Determine status class
        $statusClass = '';
        switch ($invoice['status']) {
            case 'paid':
                $statusClass = 'status-paid';
                break;
            case 'unpaid':
                $statusClass = 'status-unpaid';
                break;
            case 'overdue':
                $statusClass = 'status-overdue';
                break;
            default:
                $statusClass = '';
                break;
        }
        ?>
        <?php renderSidebar('invoices'); ?>
        <div class="main-content p-4">
            <div class="invoice-container">
                <div class="invoice-header">
                    <div class="company-logo">
                        <img src="/assets/img/company-logo.png" alt="Company Logo">
                    </div>
                    <h2>Billing Invoice</h2>
                    <div class="header-right">
                        <p>Invoice Number: <?php echo htmlspecialchars($invoice['invoice_number'] ?? ''); ?></p>
                        <p>Billing Date: <?php echo htmlspecialchars($invoice_date ?? ''); ?></p>
                        <p>Due Date: <?php echo htmlspecialchars($due_date ?? ''); ?></p>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 bill-from">
                        <strong>Bill From:</strong><br>
                        <?php echo htmlspecialchars($invoice['company_name'] ?? ''); ?><br>
                        <?php echo htmlspecialchars($invoice['company_address'] ?? ''); ?><br>
                        <?php echo htmlspecialchars(($invoice['company_city'] ?? '') . ', ' . ($invoice['company_state'] ?? '') . ' ' . ($invoice['company_postal_code'] ?? '')); ?>
                    </div>
                    <div class="col-md-6 bill-to">
                        <strong>Bill To:</strong><br>
                        <?php echo htmlspecialchars($invoice['first_name'] . ' ' . $invoice['last_name'] ?? ''); ?><br>
                        <?php echo htmlspecialchars($invoice['address'] ?? ''); ?><br>
                        <?php echo htmlspecialchars(($invoice['city'] ?? '') . ', ' . ($invoice['state'] ?? '') . ' ' . ($invoice['postal_code'] ?? '')); ?>
                    </div>
                </div>

                <table class="invoice-table">
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th>Quantity</th>
                            <th>Price</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $item_details = explode(',', $invoice['item_details'] ?? '');
                        $total_amount = 0;
                        foreach ($item_details as $item) {
                            // Split the item details into description, quantity, and price
                            $parts = explode(' x ', $item);
                            $description = $parts[0] ?? '';
                            if (count($parts) > 1) {
                                $qty_price = explode(' @ ', $parts[1] ?? '');
                                $quantity = $qty_price[0] ?? '';
                                $price = $qty_price[1] ?? '';
                            } else {
                                $quantity = 1;
                                $price = 0;
                            }
                            $total = (float)$quantity * (float)$price;
                            $total_amount += $total;
                            echo '<tr>';
                            echo '<td>' . htmlspecialchars($description) . '</td>';
                            echo '<td>' . htmlspecialchars($quantity) . '</td>';
                            echo '<td>' . htmlspecialchars($price) . '</td>';
                            echo '<td>' . htmlspecialchars($total) . '</td>';
                            echo '</tr>';
                        }
                        ?>
                    </tbody>
                </table>

                <div class="invoice-total">
                    <span class="total-amount-label">Total Amount:</span> <span class="total-amount"><?php echo htmlspecialchars($invoice['total_amount'] ?? ''); ?></span>
                </div>

                <div class="invoice-status <?php echo $statusClass; ?>">
                    <strong>Status:</strong> <?php echo htmlspecialchars($invoice['status'] ?? ''); ?>
                </div>

                <div class="btn-container">
                    <button onclick="window.print()" class="btn btn-primary">Print Billing Invoice</button>
                    <a href="/invoices.php" class="btn btn-secondary">Back to Invoices</a>
                </div>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>