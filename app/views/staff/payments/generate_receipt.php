<?php
use App\Helpers\NumberToWords;

$title = 'Payment Receipt - ISP Management System';

// Ensure proper formatting
$payment['payment_date'] = date('F d, Y', strtotime($payment['payment_date']));
$amount = (float)str_replace(',', '', $payment['amount']);
$payment['amount'] = number_format($amount, 2);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>
    <style>
        @page {
            margin: 0;
        }
        
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 30px;
            color: #333;
            background-color: #fff;
        }

        .receipt {
            max-width: 800px;
            margin: 0 auto;
            border: 1px solid #ddd;
            padding: 40px;
            position: relative;
        }

        .receipt-header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }

        .company-logo {
            max-width: 200px;
            margin-bottom: 15px;
        }

        .company-name {
            font-size: 24px;
            font-weight: bold;
            margin: 0;
        }

        .company-info {
            font-size: 14px;
            color: #666;
            margin: 5px 0;
        }

        .receipt-title {
            font-size: 20px;
            font-weight: bold;
            margin: 20px 0;
            text-align: center;
            color: #333;
            text-transform: uppercase;
        }

        .receipt-number {
            position: absolute;
            top: 40px;
            right: 40px;
            font-size: 16px;
            color: #666;
        }

        .receipt-details {
            margin: 30px 0;
            border-collapse: collapse;
            width: 100%;
        }

        .receipt-details th {
            text-align: left;
            padding: 10px;
            background-color: #f8f9fa;
            border-bottom: 1px solid #ddd;
            width: 40%;
        }

        .receipt-details td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }

        .amount {
            margin: 30px 0;
            text-align: center;
            font-size: 24px;
            font-weight: bold;
        }

        .amount-in-words {
            text-align: center;
            font-style: italic;
            color: #666;
            margin-bottom: 30px;
        }

        .receipt-footer {
            margin-top: 50px;
            text-align: center;
            font-size: 14px;
            color: #666;
        }

        .signature-line {
            margin-top: 50px;
            border-top: 1px solid #333;
            width: 200px;
            margin-left: auto;
            margin-right: auto;
            padding-top: 10px;
            text-align: center;
            font-size: 14px;
        }

        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 100px;
            color: rgba(0, 0, 0, 0.03);
            pointer-events: none;
            text-transform: uppercase;
            white-space: nowrap;
        }

        @media print {
            body {
                padding: 0;
            }

            .receipt {
                border: none;
            }

            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="receipt">
        <!-- Watermark for copy -->
        <?php if (isset($copy) && $copy): ?>
            <div class="watermark">COPY</div>
        <?php endif; ?>

        <!-- Receipt Header -->
        <div class="receipt-header">
            <img src="/img/logo.png" alt="Company Logo" class="company-logo">
            <h1 class="company-name">ISP Management System</h1>
            <p class="company-info">123 Business Street, City, Country</p>
            <p class="company-info">Phone: (123) 456-7890 | Email: info@example.com</p>
            <p class="company-info">Tax ID: 12-3456789</p>
        </div>

        <!-- Receipt Number -->
        <div class="receipt-number">
            Receipt #: <?= htmlspecialchars($payment['receipt_number']) ?>
        </div>

        <!-- Receipt Title -->
        <div class="receipt-title">
            Official Receipt
        </div>

        <!-- Receipt Details -->
        <table class="receipt-details">
            <tr>
                <th>Date</th>
                <td><?= htmlspecialchars($payment['payment_date']) ?></td>
            </tr>
            <tr>
                <th>Staff ID</th>
                <td><?= htmlspecialchars($staff['employee_id']) ?></td>
            </tr>
            <tr>
                <th>Staff Name</th>
                <td><?= htmlspecialchars($staff['first_name'] . ' ' . $staff['last_name']) ?></td>
            </tr>
            <tr>
                <th>Payment Type</th>
                <td><?= htmlspecialchars($payment['payment_type_name']) ?></td>
            </tr>
            <tr>
                <th>Payment Method</th>
                <td><?= htmlspecialchars($payment['payment_method_name']) ?></td>
            </tr>
            <?php if ($payment['reference_number']): ?>
                <tr>
                    <th>Reference Number</th>
                    <td><?= htmlspecialchars($payment['reference_number']) ?></td>
                </tr>
            <?php endif; ?>
            <tr>
                <th>Description</th>
                <td><?= htmlspecialchars($payment['description']) ?></td>
            </tr>
        </table>

        <!-- Amount -->
        <div class="amount">
            Amount Paid: $<?= htmlspecialchars($payment['amount']) ?>
        </div>

        <!-- Amount in Words -->
        <div class="amount-in-words">
            <?= NumberToWords::convert($amount) ?> Dollars Only
        </div>

        <!-- Signature -->
        <div class="signature-line">
            Authorized Signature
        </div>

        <!-- Receipt Footer -->
        <div class="receipt-footer">
            <p>Thank you for your payment</p>
            <p>This is a computer-generated receipt and does not require a physical signature</p>
            <?php if (isset($copy) && $copy): ?>
                <p><strong>COPY ONLY - NOT AN ORIGINAL RECEIPT</strong></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Print Button (Only visible on screen) -->
    <div class="no-print" style="text-align: center; margin-top: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; font-size: 16px; cursor: pointer;">
            Print Receipt
        </button>
    </div>
</body>
</html>
