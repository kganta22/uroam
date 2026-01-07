<?php
// Resend OTP API
session_start();
require_once __DIR__ . '/../../database/connect.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__, 2));
$dotenv->load();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$normalizedPhone = $_SESSION['signup_phone'] ?? null;
if (!$normalizedPhone) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Phone session not found']);
    exit();
}

// Generate OTP (6 digits)
$otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

// Delete existing OTP for this phone number
$deleteQuery = "DELETE FROM otp_verifications WHERE target = ? AND type = 'phone'";
$deleteStmt = $conn->prepare($deleteQuery);
$deleteStmt->bind_param("s", $normalizedPhone);
$deleteStmt->execute();
$deleteStmt->close();

// Hash OTP and store in database
$otpHash = password_hash($otp, PASSWORD_BCRYPT);
$expiredAt = date('Y-m-d H:i:s', time() + 600); // 10 minutes
$createdAt = date('Y-m-d H:i:s');

$insertQuery = "INSERT INTO otp_verifications (customer_id, type, target, otp_hash, expired_at, created_at) VALUES (NULL, 'phone', ?, ?, ?, ?)";
$insertStmt = $conn->prepare($insertQuery);
$insertStmt->bind_param("ssss", $normalizedPhone, $otpHash, $expiredAt, $createdAt);
$insertStmt->execute();
$insertStmt->close();

// Send OTP via Fonnte
$curl = curl_init();
$data = [
    'target' => $normalizedPhone,
    'message' => "Your uRoam verification code: " . $otp . "\n\nValid for 10 minutes. Do not share this code with anyone."
];

curl_setopt($curl, CURLOPT_HTTPHEADER, array(
    "Authorization: " . $_ENV['FONNTE_OTP_WA'],
));

curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($curl, CURLOPT_URL, "https://api.fonnte.com/send");
curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
$result = curl_exec($curl);
$curlErr = curl_error($curl);
curl_close($curl);

if ($curlErr) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to send OTP', 'error' => $curlErr]);
    exit();
}

echo json_encode(['success' => true, 'message' => 'OTP resent']);
