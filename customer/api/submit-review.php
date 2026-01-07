<?php
session_start();
require_once __DIR__ . '/../../database/connect.php';
require_once __DIR__ . '/../_guards/customerGuard.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$customer_id = $_SESSION['customer_id'] ?? null;
if (!$customer_id) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true);
$booking_code = $payload['booking_code'] ?? null;
$rating = (int) ($payload['rating'] ?? 0);
$message = trim($payload['message'] ?? '');

if (!$booking_code || $rating < 1 || $rating > 5 || empty($message)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

try {
    // Get booking details
    $stmt = $conn->prepare("
        SELECT b.booking_code, b.product_id, b.customer_name, b.customer_id
        FROM bookings b
        WHERE b.booking_code = ? AND b.customer_id = ?
    ");
    $stmt->bind_param('si', $booking_code, $customer_id);
    $stmt->execute();
    $booking = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$booking) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Booking not found']);
        exit;
    }

    // Get customer info
    $stmt = $conn->prepare("SELECT full_name, country, profile_picture FROM customers WHERE id = ?");
    $stmt->bind_param('i', $customer_id);
    $stmt->execute();
    $customer = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Insert review
    $stmt = $conn->prepare("
        INSERT INTO product_reviews 
        (booking_code, customer_id, product_id, customer_name, customer_country, customer_avatar, rating, review_message, is_published, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())
    ");
    $stmt->bind_param(
        'siisssss',
        $booking_code,
        $customer_id,
        $booking['product_id'],
        $customer['full_name'],
        $customer['country'],
        $customer['profile_picture'],
        $rating,
        $message
    );
    $stmt->execute();
    $stmt->close();

    // Mark booking as reviewed
    $stmt = $conn->prepare("UPDATE bookings SET reviewed = 1 WHERE booking_code = ?");
    $stmt->bind_param('s', $booking_code);
    $stmt->execute();
    $stmt->close();

    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Review submitted successfully']);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    exit;
}
?>