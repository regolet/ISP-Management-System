<?php
$title = 'Payslip';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payslip - <?= htmlspecialchars($payslip['employee_name']) ?></title>
    <style>
        @media print {
            body {
                margin: 0;
                padding: 0;
                font-family: Arial, sans-serif;
                font-size: 12px;
                line-height: 1.4;
            }
            .container {
                width: 21cm;
                min-height: 29.7cm;
                padding: 2cm;
                margin: 0 auto;
                background: white;
            }
            .header {
                text-align: center;
                margin-bottom: 20px;
            }
            .company-name {
                font-size: 24px;
                font-weight: bold;
                margin-bottom: 5px;
            }
            .company-address {
                margin-bottom: 20px;
            }
            .payslip-title {
                font-size: 18px;
                font-weight: bold;
                margin-bottom: 20px;
                text-align: center;
            }
            .employee-details {
                margin-bottom: 20px;
            }
            .employee-details table {
                width: 100%;
            }
            .employee-details td {
                padding: 3px 0;
            }
            .payroll-details {
                margin-bottom: 20px;
            }
            .payroll-table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 20px;
            }
            .payroll-table th,
            .payroll-table td {
                border: 1px solid #000;
                padding: 5px;
            }
            .payroll-table th {
                background-color: #f0f0f0;
                text-align: left;
            }
            .amount {
                text-align: right;
            }
            .total-row {
                font-weight: bold;
            }
            .signatures {
                margin-top: 50px;
            }
            .signature-line {
                border-top: 1px solid #000;
                width: 200px;
                margin-top: 50px;
                text-align: center;
                display: inline-block;
            }
            .no-print {
                display: none;
            }
        }

        @media screen {
            body {
                background: #f0f0f0;
                margin: 0;
                padding: 20px;
                font-family: Arial, sans-serif;
            }
            .container {
                width: 21cm;
                min-height: 29.7cm;
                padding: 2cm;
                margin: 20px auto;
                background: white;
                box-shadow: 0 0 10px rgba(0,0,0,0.1);
            }
            .print-button {
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 10px 20px;
                background: #007bff;
                color: white;
                border: none;
                border-radius: 5px;
                cursor: pointer;
            }
            .print-button:hover {
                background: #0056b3;
            }
        }
    </style>
</head>
<body>
    <button class="print-button no-print" onclick="window.print()">
        <i class="fa fa-print"></i> Print Payslip
    </button>

    <div class="container">
        <div class="header">
            <div class="company-name"><?= htmlspecialchars($company['name']) ?></div>
            <div class="company-address"><?= nl2br(htmlspecialchars($company['address'])) ?></div>
        </div>

        <div class="payslip-title">
            PAYSLIP
            <br>
            <small>For the period <?= date('F d', strtotime($payslip['period_start'])) ?> - 
                   <?= date('F d, Y', strtotime($payslip['period_end'])) ?></small>
        </div>

        <div class="employee-details">
            <table>
                <tr>
                    <td width="50%">
                        <strong>Employee Name:</strong> <?= htmlspecialchars($payslip['employee_name']) ?>
                    </td>
                    <td width="50%">
                        <strong>Employee ID:</strong> <?= htmlspecialchars($payslip['employee_code']) ?>
                    </td>
                </tr>
                <tr>
                    <td>
                        <strong>Position:</strong> <?= htmlspecialchars($payslip['position']) ?>
                    </td>
                    <td>
                        <strong>Department:</strong> <?= htmlspecialchars($payslip['department']) ?>
                    </td>
                </tr>
                <tr>
                    <td>
                        <strong>Pay Date:</strong> <?= date('F d, Y', strtotime($payslip['pay_date'])) ?>
                    </td>
                    <td>
                        <strong>Daily Rate:</strong> <?= formatCurrency($payslip['daily_rate']) ?>
                    </td>
                </tr>
            </table>
        </div>

        <div class="payroll-details">
            <table class="payroll-table">
                <tr>
                    <th colspan="2">Earnings</th>
                    <th colspan="2">Deductions</th>
                </tr>
                <tr>
                    <td>Basic Salary (<?= number_format($payslip['working_days'], 1) ?> days)</td>
                    <td class="amount"><?= formatCurrency($payslip['basic_salary']) ?></td>
                    <td>SSS Contribution</td>
                    <td class="amount"><?= formatCurrency($payslip['deductions']['sss']) ?></td>
                </tr>
                <?php if ($payslip['overtime_hours']): ?>
                    <tr>
                        <td>Overtime (<?= number_format($payslip['overtime_hours'], 1) ?> hrs)</td>
                        <td class="amount"><?= formatCurrency($payslip['overtime_pay']) ?></td>
                        <td>PhilHealth</td>
                        <td class="amount"><?= formatCurrency($payslip['deductions']['philhealth']) ?></td>
                    </tr>
                <?php endif; ?>
                <?php if ($payslip['allowances']): ?>
                    <tr>
                        <td>Allowances</td>
                        <td class="amount"><?= formatCurrency($payslip['allowances']) ?></td>
                        <td>Pag-IBIG</td>
                        <td class="amount"><?= formatCurrency($payslip['deductions']['pagibig']) ?></td>
                    </tr>
                <?php endif; ?>
                <tr>
                    <td></td>
                    <td></td>
                    <td>Tax</td>
                    <td class="amount"><?= formatCurrency($payslip['deductions']['tax']) ?></td>
                </tr>
                <?php if ($payslip['deductions']['other']): ?>
                    <tr>
                        <td></td>
                        <td></td>
                        <td>Other Deductions</td>
                        <td class="amount"><?= formatCurrency($payslip['deductions']['other']) ?></td>
                    </tr>
                <?php endif; ?>
                <tr class="total-row">
                    <td>Total Earnings</td>
                    <td class="amount"><?= formatCurrency($payslip['gross_salary']) ?></td>
                    <td>Total Deductions</td>
                    <td class="amount"><?= formatCurrency($payslip['total_deductions']) ?></td>
                </tr>
            </table>

            <table class="payroll-table">
                <tr class="total-row">
                    <td width="75%">Net Pay</td>
                    <td width="25%" class="amount"><?= formatCurrency($payslip['net_salary']) ?></td>
                </tr>
                <tr>
                    <td colspan="2" style="text-align: center;">
                        <em><?= NumberToWords::convert($payslip['net_salary']) ?></em>
                    </td>
                </tr>
            </table>
        </div>

        <div class="signatures">
            <table width="100%">
                <tr>
                    <td width="33%" style="text-align: center;">
                        <div class="signature-line">
                            Prepared by
                        </div>
                    </td>
                    <td width="33%" style="text-align: center;">
                        <div class="signature-line">
                            Checked by
                        </div>
                    </td>
                    <td width="33%" style="text-align: center;">
                        <div class="signature-line">
                            Received by
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <div style="margin-top: 30px; font-size: 10px; text-align: center;">
            This is a computer-generated document. No signature is required.
        </div>
    </div>

    <script>
    // Auto-print when loaded
    window.onload = function() {
        if (!window.location.search.includes('noprint')) {
            window.print();
        }
    };
    </script>
</body>
</html>
