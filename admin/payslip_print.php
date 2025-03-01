<?php
require_once 'config.php';
check_login();

$item_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
if (!$item_id) {
    die("Invalid request");
}

$query = "SELECT pi.*, 
          p.period_start, p.period_end, p.pay_date,
          e.employee_code, e.first_name, e.last_name, 
          e.position, e.department
          FROM payroll_items pi
          JOIN payroll_periods p ON pi.payroll_period_id = p.id
          JOIN employees e ON pi.employee_id = e.id
          WHERE pi.id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $item_id);
$stmt->execute();
$payslip = $stmt->get_result()->fetch_assoc();

// Calculate totals
$total_earnings = $payslip['basic_salary'] + $payslip['allowance'] + $payslip['overtime_amount'];
$total_deductions = $payslip['sss_contribution'] + 
                   $payslip['philhealth_contribution'] + 
                   $payslip['pagibig_contribution'] + 
                   $payslip['tax_contribution'] +
                   $payslip['late_deduction'] +
                   $payslip['absence_deduction'] +
                   $payslip['other_deductions'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Payslip - <?php echo $payslip['employee_code']; ?></title>
    <style>
        @page { size: A4; margin: 1cm; }
        @media print {
            .no-print { display: none; }
            body { padding: 0; margin: 0; }
        }
        body { 
            font-family: Arial, sans-serif; 
            font-size: 12px; 
            line-height: 1.4;
            color: #333;
        }
        .payslip { 
            max-width: 21cm;
            margin: 1cm auto;
            padding: 1cm;
            border: 1px solid #ddd;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            background: #fff;
        }
        .company-header { 
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .company-header h2 { margin: 0; font-size: 24px; }
        .company-header h3 { margin: 5px 0; font-size: 18px; }
        .company-header p { margin: 5px 0; font-size: 14px; }
        .employee-info { margin-bottom: 20px; }
        .pay-info { margin-bottom: 20px; }
        .table { 
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .table th, .table td { 
            padding: 8px;
            border: 1px solid #ddd;
            font-size: 12px;
        }
        .table th { background: #f5f5f5; }
        .total-row { font-weight: bold; background: #f8f9fa; }
        .signature-line { 
            margin-top: 40px;
            border-top: 1px solid #000;
            width: 200px;
            text-align: center;
            padding-top: 5px;
            font-size: 11px;
        }
        .summary-box {
            border: 2px solid #333;
            padding: 10px;
            margin-top: 20px;
            background: #f8f9fa;
        }
        .amount { text-align: right; }
        .payslip-footer {
            margin-top: 30px;
            font-size: 10px;
            text-align: center;
            color: #666;
        }
        .copy-label {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 14px;
            color: #666;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="no-print" style="text-align: center; padding: 20px;">
        <button onclick="window.print()">Print Payslip</button>
    </div>

    <?php for($i = 0; $i < 2; $i++): // Print two copies ?>
    <div class="payslip">
        <div class="copy-label"><?php echo $i == 0 ? "Employee's Copy" : "Company's Copy"; ?></div>
        
        <div class="company-header">
            <h2>COMPANY NAME</h2>
            <h3>PAYSLIP</h3>
            <p>For the period: <?php echo date('F d', strtotime($payslip['period_start'])); ?> - 
               <?php echo date('F d, Y', strtotime($payslip['period_end'])); ?></p>
        </div>

        <div class="employee-info">
            <table class="table">
                <tr>
                    <td width="20%"><strong>Employee ID:</strong></td>
                    <td width="30%"><?php echo htmlspecialchars($payslip['employee_code']); ?></td>
                    <td width="20%"><strong>Pay Date:</strong></td>
                    <td width="30%"><?php echo date('F d, Y', strtotime($payslip['pay_date'])); ?></td>
                </tr>
                <tr>
                    <td><strong>Name:</strong></td>
                    <td><?php echo htmlspecialchars($payslip['first_name'] . ' ' . $payslip['last_name']); ?></td>
                    <td><strong>Position:</strong></td>
                    <td><?php echo htmlspecialchars($payslip['position']); ?></td>
                </tr>
                <tr>
                    <td><strong>Department:</strong></td>
                    <td colspan="3"><?php echo htmlspecialchars($payslip['department']); ?></td>
                </tr>
            </table>
        </div>

        <div class="pay-info">
            <table class="table">
                <tr>
                    <th colspan="2">Earnings</th>
                    <th colspan="2">Deductions</th>
                </tr>
                <tr>
                    <td>Basic Salary</td>
                    <td class="amount">₱<?php echo number_format($payslip['basic_salary'], 2); ?></td>
                    <td>SSS</td>
                    <td class="amount">₱<?php echo number_format($payslip['sss_contribution'], 2); ?></td>
                </tr>
                <tr>
                    <td>Allowance</td>
                    <td class="amount">₱<?php echo number_format($payslip['allowance'], 2); ?></td>
                    <td>PhilHealth</td>
                    <td class="amount">₱<?php echo number_format($payslip['philhealth_contribution'], 2); ?></td>
                </tr>
                <tr>
                    <td>Overtime (<?php echo $payslip['overtime_hours']; ?> hrs)</td>
                    <td class="amount">₱<?php echo number_format($payslip['overtime_amount'], 2); ?></td>
                    <td>Pag-IBIG</td>
                    <td class="amount">₱<?php echo number_format($payslip['pagibig_contribution'], 2); ?></td>
                </tr>
                <tr>
                    <td></td>
                    <td></td>
                    <td>Tax</td>
                    <td class="amount">₱<?php echo number_format($payslip['tax_contribution'], 2); ?></td>
                </tr>
                <tr>
                    <td></td>
                    <td></td>
                    <td>Late/Absences</td>
                    <td class="amount">₱<?php echo number_format($payslip['late_deduction'] + $payslip['absence_deduction'], 2); ?></td>
                </tr>
                <tr class="total-row">
                    <td>Total Earnings</td>
                    <td class="amount">₱<?php echo number_format($total_earnings, 2); ?></td>
                    <td>Total Deductions</td>
                    <td class="amount">₱<?php echo number_format($total_deductions, 2); ?></td>
                </tr>
            </table>

            <div class="summary-box">
                <table class="table" style="margin:0">
                    <tr class="total-row">
                        <td width="75%"><strong>NET PAY</strong></td>
                        <td class="amount"><strong>₱<?php echo number_format($payslip['net_salary'], 2); ?></strong></td>
                    </tr>
                </table>
            </div>
        </div>

        <div style="display: flex; justify-content: space-between; margin-top: 40px;">
            <div class="signature-line">Employee's Signature</div>
            <div class="signature-line">Authorized Signature</div>
        </div>

        <div class="payslip-footer">
            This is a computer-generated document. No signature is required.
            <br>Generated on <?php echo date('F d, Y h:i A'); ?>
        </div>
    </div>

    <?php if ($i == 0): ?>
    <div style="page-break-after: always;"></div>
    <?php endif; ?>
    <?php endfor; ?>
</body>
</html>
