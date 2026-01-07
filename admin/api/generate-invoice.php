<?php
session_start();
require_once __DIR__ . '/../../database/connect.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

header('Content-Type: application/json');

// ====== VERIFY ADMIN IS LOGGED IN ======
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$booking_code = $_POST['booking_code'] ?? null;

if (!$booking_code) {
    echo json_encode(['success' => false, 'message' => 'Booking code is required']);
    exit;
}

try {
    // ====== CHECK IF INVOICE ALREADY EXISTS ======
    $checkQuery = $conn->prepare("SELECT invoice_path FROM invoices WHERE booking_code = ?");
    if (!$checkQuery) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $checkQuery->bind_param('s', $booking_code);
    $checkQuery->execute();
    $existingInvoice = $checkQuery->get_result()->fetch_assoc();
    $checkQuery->close();

    // If invoice exists and file is present, return the path
    if ($existingInvoice && !empty($existingInvoice['invoice_path'])) {
        $filePath = __DIR__ . '/../../' . $existingInvoice['invoice_path'];
        if (file_exists($filePath)) {
            echo json_encode([
                'success' => true,
                'invoice_path' => '/PROGNET/' . $existingInvoice['invoice_path'],
                'message' => 'Invoice already exists'
            ]);
            exit;
        }
    }

    // ====== FETCH BOOKING DETAILS ======
    $bookingQuery = $conn->prepare("
        SELECT 
            b.booking_code,
            b.activity_date,
            b.purchase_date,
            b.option_name,
            b.total_adult,
            b.total_child,
            b.net_rate,
            b.customer_name,
            b.email,
            b.phone,
            p.title AS product_name
        FROM bookings b
        JOIN products p ON b.product_id = p.id
        WHERE b.booking_code = ?
    ");
    if (!$bookingQuery) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $bookingQuery->bind_param('s', $booking_code);
    if (!$bookingQuery->execute()) {
        throw new Exception("Execute failed: " . $bookingQuery->error);
    }
    $booking = $bookingQuery->get_result()->fetch_assoc();
    $bookingQuery->close();

    if (!$booking) {
        echo json_encode(['success' => false, 'message' => 'Booking not found']);
        exit;
    }

    // ====== GENERATE INVOICE HTML ======
    $companyQuery = $conn->prepare("SELECT customer_service_email, customer_service_phone FROM company_profile LIMIT 1");
    $companyQuery->execute();
    $company = $companyQuery->get_result()->fetch_assoc();
    $companyQuery->close();

    $html = generateInvoiceHTML($booking, $company);

    // ====== GENERATE PDF ======
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true);

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    // ====== SAVE PDF TO FILE ======
    $invoiceFileName = 'invoice_' . $booking_code . '_' . time() . '.pdf';
    $invoiceDir = __DIR__ . '/../../database/uploads/invoices/';

    // Check if directory exists
    if (!is_dir($invoiceDir)) {
        if (!mkdir($invoiceDir, 0755, true)) {
            throw new Exception("Failed to create invoices directory");
        }
    }

    $invoiceFullPath = $invoiceDir . $invoiceFileName;
    $invoiceDBPath = '/PROGNET/database/uploads/invoices/' . $invoiceFileName;

    if (!file_put_contents($invoiceFullPath, $dompdf->output())) {
        throw new Exception("Failed to save PDF file");
    }

    // ====== UPDATE DATABASE ======
    if ($existingInvoice) {
        // Update existing invoice record
        $updateQuery = $conn->prepare("UPDATE invoices SET invoice_path = ?, status = 'generated' WHERE booking_code = ?");
        if (!$updateQuery) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $updateQuery->bind_param('ss', $invoiceDBPath, $booking_code);
        if (!$updateQuery->execute()) {
            throw new Exception("Update failed: " . $updateQuery->error);
        }
        $updateQuery->close();
    } else {
        // Insert new invoice record
        $insertQuery = $conn->prepare("INSERT INTO invoices (booking_code, invoice_path, status) VALUES (?, ?, 'generated')");
        if (!$insertQuery) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $insertQuery->bind_param('ss', $booking_code, $invoiceDBPath);
        if (!$insertQuery->execute()) {
            throw new Exception("Insert failed: " . $insertQuery->error);
        }
        $insertQuery->close();
    }

    echo json_encode([
        'success' => true,
        'invoice_path' => $invoiceDBPath,
        'message' => 'Invoice generated successfully'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    exit;
}

// ====== INVOICE HTML TEMPLATE ======
function generateInvoiceHTML($booking, $company = null)
{
    $invoiceDate = date('F j, Y');
    $activityDate = date('F j, Y', strtotime($booking['activity_date']));
    $purchaseDate = date('F j, Y', strtotime($booking['purchase_date']));

    // Default company info if not provided
    if (!$company) {
        $company = [
            'customer_service_email' => 'support@uroam.com',
            'customer_service_phone' => '+62 123-456-7890'
        ];
    }

    return '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Solway, Arial, sans-serif;
            padding: 50px;
            color: #333;
            background: #fff;
        }
        .header {
            border-bottom: 3px solid #ea580c;
            padding-bottom: 25px;
            margin-bottom: 40px;
        }
        .company-name {
            font-size: 36px;
            font-weight: 700;
            color: #ea580c;
            margin-bottom: 3px;
            letter-spacing: -0.5px;
        }
        .company-tagline {
            font-size: 12px;
            color: #999;
            font-weight: 400;
        }
        .invoice-title {
            font-size: 28px;
            color: #444;
            margin-top: 15px;
            letter-spacing: 2px;
            font-weight: 600;
        }
        .info-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 35px;
            gap: 30px;
        }
        .info-block {
            flex: 1;
        }
        .info-block h3 {
            font-size: 11px;
            color: #ea580c;
            margin-bottom: 12px;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 1px;
        }
        .info-block p {
            font-size: 13px;
            line-height: 1.8;
            margin: 4px 0;
            color: #555;
        }
        .info-block strong {
            color: #333;
            font-weight: 600;
        }
        .invoice-details {
            background: #fafafa;
            padding: 22px;
            margin-bottom: 35px;
            border-radius: 6px;
            border-left: 4px solid #ea580c;
        }
        .invoice-details h3 {
            color: #ea580c;
            margin-bottom: 15px;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 700;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin: 10px 0;
            font-size: 13px;
        }
        .detail-label {
            font-weight: 600;
            color: #666;
            min-width: 120px;
        }
        .detail-value {
            color: #333;
            text-align: right;
            flex: 1;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 35px 0;
        }
        .items-table th {
            background: #ea580c;
            color: white;
            padding: 14px 12px;
            text-align: left;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .items-table td {
            padding: 14px 12px;
            border-bottom: 1px solid #e8e8e8;
            font-size: 13px;
            color: #444;
        }
        .items-table tr:last-child td {
            border-bottom: 2px solid #ea580c;
        }
        .total-section {
            margin-top: 35px;
            text-align: left;
        }
        .total-row {
            display: block;
            margin: 12px 0;
            font-size: 14px;
        }
        .total-label {
            width: auto;
            text-align: left;
            font-weight: 600;
            color: #666;
            display: inline;
            margin-right: 10px;
        }
        .total-value {
            width: auto;
            text-align: left;
            color: #333;
            font-weight: 500;
            display: inline;
        }
        .grand-total {
            font-size: 16px;
            color: #ea580c;
            border-top: 2px solid #ea580c;
            border-bottom: 2px solid #ea580c;
            padding: 12px 0;
            margin-top: 15px;
        }
        .grand-total .total-label {
            font-weight: 700;
            font-size: 15px;
        }
        .grand-total .total-value {
            font-weight: 700;
            font-size: 15px;
        }
        .footer {
            margin-top: 50px;
            padding-top: 25px;
            border-top: 1px solid #e8e8e8;
            text-align: center;
            font-size: 11px;
            color: #888;
        }
        .footer p {
            margin: 4px 0;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">uRoam</div>
        <div class="company-tagline">Your Travel Companion</div>
        <div class="invoice-title">INVOICE</div>
    </div>

    <div class="info-section">
        <div class="info-block">
            <h3>Invoice To:</h3>
            <p><strong>' . htmlspecialchars($booking['customer_name']) . '</strong></p>
            <p>' . htmlspecialchars($booking['email']) . '</p>
            <p>' . htmlspecialchars($booking['phone']) . '</p>
        </div>
        <div class="info-block">
            <p><strong>Invoice Number:</strong> ' . htmlspecialchars($booking['booking_code']) . '</p>
            <p><strong>Invoice Date:</strong> ' . $invoiceDate . '</p>
            <p><strong>Purchase Date:</strong> ' . $purchaseDate . '</p>
        </div>
    </div>

    <div class="invoice-details" style="display: none;">
        <h3>Booking Details</h3>
        <div class="detail-row">
            <span class="detail-label">Product:</span>
            <span class="detail-value">' . htmlspecialchars($booking['product_name']) . '</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Activity Date:</span>
            <span class="detail-value">' . $activityDate . '</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Option:</span>
            <span class="detail-value">' . ucfirst(htmlspecialchars($booking['option_name'])) . '</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Participants:</span>
            <span class="detail-value">' . $booking['total_adult'] . ' Adults' . ($booking['total_child'] > 0 ? ', ' . $booking['total_child'] . ' Children' : '') . '</span>
        </div>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th>Description</th>
                <th style="text-align: center; width: 100px;">Quantity</th>
                <th style="text-align: right; width: 150px;">Unit Price</th>
                <th style="text-align: right; width: 150px;">Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>' . htmlspecialchars($booking['product_name']) . ' - ' . ucfirst($booking['option_name']) . '</td>
                <td style="text-align: center;">' . ($booking['total_adult'] + $booking['total_child']) . ' pax</td>
                <td style="text-align: right;">IDR ' . number_format($booking['net_rate'] / ($booking['total_adult'] + $booking['total_child']), 0, '.', ',') . '</td>
                <td style="text-align: right;">IDR ' . number_format($booking['net_rate'], 0, '.', ',') . '</td>
            </tr>
        </tbody>
    </table>

    <div class="total-section">
        <div class="total-row">
            <div class="total-label">Subtotal:</div>
            <div class="total-value">IDR ' . number_format($booking['net_rate'], 0, '.', ',') . '</div>
        </div>
        <div class="total-row grand-total">
            <div class="total-label">TOTAL AMOUNT:</div>
            <div class="total-value">IDR ' . number_format($booking['net_rate'], 0, '.', ',') . '</div>
        </div>
    </div>

    <div class="footer">
        <p><strong>Thank you for booking with uRoam!</strong></p>
        <p>This is a computer-generated invoice and does not require a signature.</p>
        <p>For questions or inquiries, please contact us at ' . htmlspecialchars($company['customer_service_email']) . ' or ' . htmlspecialchars($company['customer_service_phone']) . '</p>
    </div>
</body>
</html>
    ';
}
