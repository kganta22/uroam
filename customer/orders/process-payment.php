<?php
// Ensure log directory exists before routing PHP errors there
$logDir = __DIR__ . '/../../logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0777, true);
}

// Suppress HTML error output - return JSON only
ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', $logDir . '/payment-error.log');

header('Content-Type: application/json');

try {
    // Load dependencies
    require_once __DIR__ . '/../../vendor/autoload.php';
    require_once __DIR__ . '/../../database/connect.php';

    // Load .env file
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
    $dotenv->load();

    // Verify POST request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit();
    }

    // Parse payload
    $payload = json_decode(file_get_contents('php://input'), true);
    $booking_code = $payload['booking_code'] ?? null; // original order_request code (ORDxxxx)
    $meeting_point = $payload['meeting_point'] ?? null;
    $use_points = $payload['use_points'] ?? false;
    $points_used = $payload['points_used'] ?? 0;

    // Validation
    if (!$booking_code || !$meeting_point) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit();
    }

    // Get order details (complete) and verify it is ready for payment
    $stmt = $conn->prepare("
        SELECT 
            o.booking_code,
            o.customer_id,
            o.product_id,
            o.option_name,
            o.customer_name,
            o.phone,
            o.email,
            o.total_adult,
            o.total_child,
            o.gross_rate,
            o.duration,
            o.purchase_date,
            o.activity_date,
            p.duration_hours
        FROM order_request o
        JOIN products p ON p.id = o.product_id
        WHERE o.booking_code = ? AND o.status = 'payment'
    ");
    $stmt->bind_param('s', $booking_code);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();

    if (!$order) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Order not found or not ready for payment']);
        exit();
    }

    $customer_id = $order['customer_id'];

    // Calculate final amount with min charge guard (leave at least 1,000 if price allows)
    $gross_rate = (float) $order['gross_rate'];
    $min_charge = 1000; // keep small payable amount so Midtrans accepts
    $max_deduction = max(0, $gross_rate - $min_charge);
    $points_used = min((float) $points_used, $max_deduction);
    $final_amount = $gross_rate - $points_used;

    // Get Midtrans credentials
    $midtrans_server_key = $_ENV['MIDTRANS_SERVER_KEY'] ?? null;
    $midtrans_client_key = $_ENV['MIDTRANS_CLIENT_KEY'] ?? null;

    if (!$midtrans_server_key || !$midtrans_client_key) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Payment gateway not configured']);
        exit();
    }

    // Get customer details from database
    $customer_stmt = $conn->prepare("SELECT first_name, last_name, email, phone FROM customers WHERE id = ?");
    $customer_stmt->bind_param('i', $customer_id);
    $customer_stmt->execute();
    $customer_data = $customer_stmt->get_result()->fetch_assoc();

    $customer_first_name = $customer_data['first_name'] ?? 'Customer';
    $customer_last_name = $customer_data['last_name'] ?? '';
    $customer_email = $customer_data['email'] ?? 'customer@uroam.com';
    $customer_phone = $customer_data['phone'] ?? '';

    // Validate email
    if (!filter_var($customer_email, FILTER_VALIDATE_EMAIL)) {
        $customer_email = 'customer@uroam.com';
    }

    // Prepare Snap payload
    $snap_order_id = $booking_code . '_' . time();

    $snap_payload = [
        'transaction_details' => [
            'order_id' => $snap_order_id,
            'gross_amount' => (int) $final_amount,
        ],
        'customer_details' => [
            'first_name' => $customer_first_name,
            'last_name' => $customer_last_name,
            'email' => $customer_email,
            'phone' => $customer_phone,
        ],
    ];

    // Call Midtrans API
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://app.sandbox.midtrans.com/snap/v1/transactions');
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_USERPWD, $midtrans_server_key);
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($snap_payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $midtrans_response = curl_exec($ch);
    $curl_error = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($curl_error) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'API connection error: ' . $curl_error]);
        exit();
    }

    if ($http_code !== 201) {
        $response_data = json_decode($midtrans_response, true);
        $error_msg = 'Midtrans API error (HTTP ' . $http_code . ')';
        if (isset($response_data['error_messages'])) {
            $error_msg .= ': ' . implode(', ', (array) $response_data['error_messages']);
        }
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $error_msg, 'debug' => ['payload' => $snap_payload, 'response' => $response_data]]);
        exit();
    }

    $response_data = json_decode($midtrans_response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Invalid API response']);
        exit();
    }

    if (!isset($response_data['token'])) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'No token in response', 'debug' => $response_data]);
        exit();
    }

    // Do not move data yet; just return token. Finalization happens after payment success.
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'token' => $response_data['token'],
        'order_id' => $snap_order_id,
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>