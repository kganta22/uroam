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

$customerId = $_SESSION['customer_id'] ?? null;
if (!$customerId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Read JSON payload
$payload = json_decode(file_get_contents('php://input'), true);
if (!$payload) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid payload']);
    exit;
}

$productId = isset($payload['product_id']) ? (int) $payload['product_id'] : 0;
$priceId = isset($payload['price_id']) ? (int) $payload['price_id'] : 0;
$adultCount = isset($payload['adult_count']) ? (int) $payload['adult_count'] : 0;
$childCount = isset($payload['child_count']) ? (int) $payload['child_count'] : 0;
$activityDate = isset($payload['activity_date']) ? trim($payload['activity_date']) : '';

if ($productId <= 0 || $priceId <= 0 || ($adultCount + $childCount) <= 0 || empty($activityDate)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing or invalid booking data']);
    exit;
}

// Validate activity date (should be in MM-DD-YYYY HH:MM format)
$dateTimeParts = explode(' ', $activityDate);
if (count($dateTimeParts) < 2) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Activity date and time are required']);
    exit;
}

$datePart = $dateTimeParts[0]; // MM-DD-YYYY
$timePart = $dateTimeParts[1]; // HH:MM

$dateObj = DateTime::createFromFormat('m-d-Y H:i', $datePart . ' ' . $timePart);
if (!$dateObj) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid date/time format']);
    exit;
}
$normalizedDateTime = $dateObj->format('Y-m-d H:i:s');

// Load price row
$priceStmt = $conn->prepare('SELECT product_id, category, adult_price, child_price FROM product_prices WHERE id = ?');
$priceStmt->bind_param('i', $priceId);
$priceStmt->execute();
$price = $priceStmt->get_result()->fetch_assoc();
$priceStmt->close();

if (!$price || (int) $price['product_id'] !== $productId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid price option']);
    exit;
}

// Load product info
$productStmt = $conn->prepare('SELECT title, duration_hours FROM products WHERE id = ? AND is_active = 1');
$productStmt->bind_param('i', $productId);
$productStmt->execute();
$product = $productStmt->get_result()->fetch_assoc();
$productStmt->close();

if (!$product) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Product not found']);
    exit;
}

// Load customer info
$customerStmt = $conn->prepare('SELECT full_name, email, phone FROM customers WHERE id = ?');
$customerStmt->bind_param('i', $customerId);
$customerStmt->execute();
$customer = $customerStmt->get_result()->fetch_assoc();
$customerStmt->close();

if (!$customer) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Customer not found']);
    exit;
}

// Calculate pricing
$grossRate = ($adultCount * (int) $price['adult_price']) + ($childCount * (int) $price['child_price']);
$optionName = ucfirst($price['category']);

// Generate booking code using order_sequence table
$conn->begin_transaction();
try {
    $conn->query('INSERT INTO order_sequence () VALUES ()');
    $seqId = $conn->insert_id;
    $bookingCode = 'ORD' . str_pad((string) $seqId, 5, '0', STR_PAD_LEFT);

    $insertStmt = $conn->prepare('INSERT INTO order_request 
        (booking_code, customer_id, product_id, option_name, customer_name, total_adult, total_child, gross_rate, duration, phone, email, purchase_date, activity_date, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "request")');
    $purchaseDate = null; // leave null on initial request; will be filled on payment
    $durationText = $product['duration_hours'] ? $product['duration_hours'] . ' hours' : null;
    // Types: s booking_code, i customer_id, i product_id, s option_name, s customer_name,
    // i total_adult, i total_child, d gross_rate, s duration, s phone, s email, s purchase_date, s activity_date
    $insertStmt->bind_param(
        'siissiidsssss',
        $bookingCode,
        $customerId,
        $productId,
        $optionName,
        $customer['full_name'],
        $adultCount,
        $childCount,
        $grossRate,
        $durationText,
        $customer['phone'],
        $customer['email'],
        $purchaseDate,
        $normalizedDateTime
    );
    $insertStmt->execute();
    $insertStmt->close();

    $conn->commit();

    echo json_encode([
        'success' => true,
        'booking_code' => $bookingCode,
        'redirect' => '/PROGNET/customer/orders/reviews.php'
    ]);
    exit;
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to create order request']);
    exit;
}
