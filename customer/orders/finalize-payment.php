<?php
// Finalize booking after successful Midtrans payment (server-verifies status)
$logDir = __DIR__ . '/../../logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0777, true);
}
ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', $logDir . '/payment-error.log');

header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../../vendor/autoload.php';
    require_once __DIR__ . '/../../database/connect.php';

    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
    $dotenv->load();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit();
    }

    $payload = json_decode(file_get_contents('php://input'), true);
    $booking_code = $payload['booking_code'] ?? null; // original order_request code
    $snap_order_id = $payload['order_id'] ?? null;    // snap order_id
    $meeting_point = $payload['meeting_point'] ?? null;
    $use_points = $payload['use_points'] ?? false;
    $points_used = $payload['points_used'] ?? 0;

    if (!$booking_code || !$meeting_point || !$snap_order_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit();
    }

    // Verify payment status with Midtrans
    $midtrans_server_key = $_ENV['MIDTRANS_SERVER_KEY'] ?? null;
    if (!$midtrans_server_key) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Payment gateway not configured']);
        exit();
    }

    $statusCh = curl_init();
    curl_setopt($statusCh, CURLOPT_URL, 'https://api.sandbox.midtrans.com/v2/' . urlencode($snap_order_id) . '/status');
    curl_setopt($statusCh, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($statusCh, CURLOPT_USERPWD, $midtrans_server_key . ':');
    curl_setopt($statusCh, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($statusCh, CURLOPT_TIMEOUT, 20);
    curl_setopt($statusCh, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($statusCh, CURLOPT_SSL_VERIFYPEER, false);

    $statusResponse = curl_exec($statusCh);
    $statusError = curl_error($statusCh);
    $statusHttp = curl_getinfo($statusCh, CURLINFO_HTTP_CODE);
    curl_close($statusCh);

    if ($statusError) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to verify payment: ' . $statusError]);
        exit();
    }

    $statusData = json_decode($statusResponse, true);
    if ($statusHttp !== 200 || json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Invalid payment status response', 'debug' => $statusResponse]);
        exit();
    }

    $transaction_status = $statusData['transaction_status'] ?? null;
    $fraud_status = $statusData['fraud_status'] ?? null;

    $isPaid = in_array($transaction_status, ['settlement', 'capture']) && ($transaction_status !== 'capture' || $fraud_status === 'accept');
    if (!$isPaid) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Payment not completed yet', 'status' => $transaction_status, 'fraud_status' => $fraud_status]);
        exit();
    }

    // Fetch order_request (still in payment status)
    $stmt = $conn->prepare("\n        SELECT \n            o.booking_code, o.customer_id, o.product_id, o.option_name, o.customer_name, o.phone, o.email,\n            o.total_adult, o.total_child, o.gross_rate, o.duration, o.purchase_date, o.activity_date,\n            p.duration_hours\n        FROM order_request o\n        JOIN products p ON p.id = o.product_id\n        WHERE o.booking_code = ? AND o.status = 'payment'\n    ");
    $stmt->bind_param('s', $booking_code);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();

    if (!$order) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Order not found or not ready for payment']);
        exit();
    }

    $customer_id = $order['customer_id'];

    // Calculate capped points and net
    $gross_rate = (float) $order['gross_rate'];
    $min_charge = 1000;
    $max_deduction = max(0, $gross_rate - $min_charge);
    $points_used = min((float) $points_used, $max_deduction);
    $discount_rate = $points_used;
    $net_rate = $gross_rate - $discount_rate;

    // Normalize dates with time
    $purchase_date = date('Y-m-d H:i:s', strtotime($order['purchase_date']));

    // Ensure activity_date has proper time format (Y-m-d H:i:s)
    $activity_datetime = new DateTime($order['activity_date']);
    $activity_date = $activity_datetime->format('Y-m-d H:i:s');

    // Duration normalization
    $duration_value = (int) ($order['duration_hours'] ?? 0);
    if ($duration_value === 0 && !empty($order['duration'])) {
        if (preg_match('/\d+/', $order['duration'], $m)) {
            $duration_value = (int) $m[0];
        }
    }

    $conn->begin_transaction();
    try {
        // Generate new booking_code
        $conn->query("INSERT INTO booking_sequence () VALUES ()");
        $new_id = $conn->insert_id;
        $new_booking_code = 'BK' . str_pad((string) $new_id, 5, '0', STR_PAD_LEFT);

        // Insert into bookings
        $insert = $conn->prepare("\n            INSERT INTO bookings (\n                booking_code, customer_id, product_id, option_name, customer_name, phone, email,\n                total_adult, total_child, gross_rate, discount_rate, net_rate, duration,\n                meeting_point, purchase_date, activity_date\n            ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)\n        ");

        if ($insert === false) {
            throw new Exception('DB error (prepare insert bookings): ' . $conn->error);
        }

        $insert->bind_param(
            'siissssiidddisss',
            $new_booking_code,
            $order['customer_id'],
            $order['product_id'],
            $order['option_name'],
            $order['customer_name'],
            $order['phone'],
            $order['email'],
            $order['total_adult'],
            $order['total_child'],
            $order['gross_rate'],
            $discount_rate,
            $net_rate,
            $duration_value,
            $meeting_point,
            $purchase_date,
            $activity_date
        );

        if (!$insert->execute()) {
            throw new Exception('Failed to insert booking: ' . $insert->error);
        }

        // Delete from order_request
        $delete = $conn->prepare("DELETE FROM order_request WHERE booking_code = ?");
        if ($delete === false) {
            throw new Exception('DB error (prepare delete order_request): ' . $conn->error);
        }
        $delete->bind_param('s', $booking_code);
        if (!$delete->execute()) {
            throw new Exception('Failed to delete order_request: ' . $delete->error);
        }

        // Deduct points if used
        if ($use_points && $points_used > 0) {
            $points_deduction = (int) $points_used;
            $points_stmt = $conn->prepare("UPDATE customers SET point = point - ? WHERE id = ?");
            if ($points_stmt === false) {
                throw new Exception('DB error (prepare update points): ' . $conn->error);
            }
            $points_stmt->bind_param('ii', $points_deduction, $customer_id);
            if (!$points_stmt->execute()) {
                throw new Exception('Failed to update customer points: ' . $points_stmt->error);
            }
        }

        // Add earned points (13% of final amount paid)
        $earned_points = (int) ($net_rate * 0.13);
        if ($earned_points > 0) {
            $earn_stmt = $conn->prepare("UPDATE customers SET point = point + ? WHERE id = ?");
            if ($earn_stmt === false) {
                throw new Exception('DB error (prepare add earned points): ' . $conn->error);
            }
            $earn_stmt->bind_param('ii', $earned_points, $customer_id);
            if (!$earn_stmt->execute()) {
                throw new Exception('Failed to add earned points: ' . $earn_stmt->error);
            }
        }

        $conn->commit();

    } catch (Exception $tx) {
        $conn->rollback();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to finalize booking: ' . $tx->getMessage()]);
        exit();
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'booking_code' => $new_booking_code,
        'redirect_url' => '/PROGNET/customer/orders/payment-success.php?booking_code=' . $new_booking_code,
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>