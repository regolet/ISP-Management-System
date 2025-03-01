<?php
require_once '../../config.php';
require_once '../staff_auth.php';
require_once '../../vendor/autoload.php'; // For TCPDF

// Get payment details
$payment_id = clean_input($_GET['id']);
$stmt = $conn->prepare("
    SELECT p.*, 
           c.name as customer_name, c.address as customer_address,
           b.invoiceid, b.amount as bill_amount,
           e.first_name, e.last_name
    FROM payments p
    LEFT JOIN customers c ON p.customer_id = c.id
    LEFT JOIN billing b ON p.billing_id = b.id
    LEFT JOIN employees e ON p.created_by = e.id
    WHERE p.id = ?
");
$stmt->bind_param("i", $payment_id);
$stmt->execute();
$payment = $stmt->get_result()->fetch_assoc();

if (!$payment) {
    die("Payment not found");
}

// Create PDF
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('ISP System');
$pdf->SetAuthor('Staff Portal');
$pdf->SetTitle('Payment Receipt #' . $payment_id);

// Remove header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Add a page
$pdf->AddPage();

// Set font
$pdf->SetFont('helvetica', '', 12);

// Company Logo and Info
$pdf->Image('../../assets/images/logo.png', 15, 10, 30);
$pdf->Cell(0, 10, 'Your ISP Company Name', 0, 1, 'R');
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 5, 'Address: 123 Main Street', 0, 1, 'R');
$pdf->Cell(0, 5, 'Phone: (123) 456-7890', 0, 1, 'R');

// Receipt Title
$pdf->Ln(10);
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'OFFICIAL RECEIPT', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 5, 'Receipt No: ' . str_pad($payment_id, 8, '0', STR_PAD_LEFT), 0, 1, 'C');

// Customer Info
$pdf->Ln(10);
$pdf->SetFont('helvetica', 'B', 11);
$pdf->Cell(40, 7, 'Received from:', 0);
$pdf->SetFont('helvetica', '', 11);
$pdf->Cell(0, 7, $payment['customer_name'], 0, 1);

$pdf->SetFont('helvetica', 'B', 11);
$pdf->Cell(40, 7, 'Address:', 0);
$pdf->SetFont('helvetica', '', 11);
$pdf->Cell(0, 7, $payment['customer_address'], 0, 1);

$pdf->SetFont('helvetica', 'B', 11);
$pdf->Cell(40, 7, 'Date:', 0);
$pdf->SetFont('helvetica', '', 11);
$pdf->Cell(0, 7, date('F d, Y', strtotime($payment['payment_date'])), 0, 1);

// Payment Details
$pdf->Ln(5);
$pdf->SetFont('helvetica', 'B', 11);
$pdf->Cell(0, 7, 'Payment Details', 'B', 1);

$pdf->Ln(5);
$pdf->SetFont('helvetica', '', 11);
$pdf->Cell(100, 7, 'Invoice Number: #' . $payment['invoiceid'], 0, 0);
$pdf->Cell(0, 7, 'Amount Due: ₱' . number_format($payment['bill_amount'], 2), 0, 1);

$pdf->Cell(100, 7, 'Payment Method: ' . ucwords(str_replace('_', ' ', $payment['payment_method'])), 0, 0);
$pdf->Cell(0, 7, 'Amount Paid: ₱' . number_format($payment['amount'], 2), 0, 1);

$pdf->Cell(100, 7, 'Reference Number: ' . $payment['reference_number'], 0, 1);

// Notes
if (!empty($payment['notes'])) {
    $pdf->Ln(5);
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(0, 7, 'Notes:', 0, 1);
    $pdf->SetFont('helvetica', '', 11);
    $pdf->MultiCell(0, 7, $payment['notes'], 0, 'L');
}

// Signatures
$pdf->Ln(20);
$pdf->Cell(95, 7, 'Received by:', 0, 0);
$pdf->Cell(95, 7, 'Customer Signature:', 0, 1);
$pdf->Cell(95, 7, $payment['first_name'] . ' ' . $payment['last_name'], 'T', 0, 'C');
$pdf->Cell(95, 7, '', 'T', 1, 'C');

// Output PDF
$pdf->Output('Receipt_' . str_pad($payment_id, 8, '0', STR_PAD_LEFT) . '.pdf', 'I');
