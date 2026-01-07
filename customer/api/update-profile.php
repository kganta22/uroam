<?php
/* API to update customer profile */
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../database/connect.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__, 2));
$dotenv->load();

// Check if customer is logged in
if (!isset($_SESSION['customer_id'])) {
  echo json_encode(['success' => false, 'message' => 'Unauthorized']);
  exit;
}

$customerId = $_SESSION['customer_id'];
$action = $_POST['action'] ?? '';

try {
  switch ($action) {
    case 'update_first_name':
      $firstName = trim($_POST['first_name'] ?? '');

      if (empty($firstName)) {
        echo json_encode(['success' => false, 'message' => 'First name is required']);
        exit;
      }

      if (!preg_match('/^[a-zA-Z\s\-\']+$/', $firstName)) {
        echo json_encode(['success' => false, 'message' => 'First name can only contain letters']);
        exit;
      }

      // Get last name to update full name
      $stmt = $conn->prepare("SELECT last_name FROM customers WHERE id = ?");
      $stmt->bind_param("i", $customerId);
      $stmt->execute();
      $result = $stmt->get_result();
      $customer = $result->fetch_assoc();
      $stmt->close();

      $fullName = $firstName . ' ' . ($customer['last_name'] ?? '');

      $stmt = $conn->prepare("UPDATE customers SET first_name = ?, full_name = ? WHERE id = ?");
      $stmt->bind_param("ssi", $firstName, $fullName, $customerId);
      $stmt->execute();
      $stmt->close();

      // Update session
      $_SESSION['customer_name'] = $fullName;

      echo json_encode(['success' => true, 'message' => 'First name updated successfully']);
      break;

    case 'update_last_name':
      $lastName = trim($_POST['last_name'] ?? '');

      if (empty($lastName)) {
        echo json_encode(['success' => false, 'message' => 'Last name is required']);
        exit;
      }

      if (!preg_match('/^[a-zA-Z\s\-\']+$/', $lastName)) {
        echo json_encode(['success' => false, 'message' => 'Last name can only contain letters']);
        exit;
      }

      // Get first name to update full name
      $stmt = $conn->prepare("SELECT first_name FROM customers WHERE id = ?");
      $stmt->bind_param("i", $customerId);
      $stmt->execute();
      $result = $stmt->get_result();
      $customer = $result->fetch_assoc();
      $stmt->close();

      $fullName = ($customer['first_name'] ?? '') . ' ' . $lastName;

      $stmt = $conn->prepare("UPDATE customers SET last_name = ?, full_name = ? WHERE id = ?");
      $stmt->bind_param("ssi", $lastName, $fullName, $customerId);
      $stmt->execute();
      $stmt->close();

      // Update session
      $_SESSION['customer_name'] = $fullName;

      echo json_encode(['success' => true, 'message' => 'Last name updated successfully']);
      break;

    case 'update_country':
      $country = trim($_POST['country'] ?? '');

      if (empty($country)) {
        echo json_encode(['success' => false, 'message' => 'Country is required']);
        exit;
      }

      $stmt = $conn->prepare("UPDATE customers SET country = ? WHERE id = ?");
      $stmt->bind_param("si", $country, $customerId);
      $stmt->execute();
      $stmt->close();

      echo json_encode(['success' => true, 'message' => 'Country updated successfully']);
      break;

    case 'update_email':
      $newEmail = trim($_POST['email'] ?? '');

      if (empty($newEmail)) {
        echo json_encode(['success' => false, 'message' => 'Email is required']);
        exit;
      }

      if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        exit;
      }

      // Check if email already exists for another customer
      $checkStmt = $conn->prepare("SELECT id FROM customers WHERE email = ? AND id != ?");
      $checkStmt->bind_param("si", $newEmail, $customerId);
      $checkStmt->execute();
      $checkResult = $checkStmt->get_result();
      if ($checkResult->num_rows > 0) {
        $checkStmt->close();
        echo json_encode(['success' => false, 'message' => 'Email already in use']);
        exit;
      }
      $checkStmt->close();

      // Update email
      $stmt = $conn->prepare("UPDATE customers SET email = ? WHERE id = ?");
      $stmt->bind_param("si", $newEmail, $customerId);
      $stmt->execute();
      $stmt->close();

      // Update session
      $_SESSION['customer_email'] = $newEmail;

      // Reset email verification status and update target to new email in otp_verifications
      $resetStmt = $conn->prepare("UPDATE otp_verifications SET verified_at = NULL, target = ? WHERE customer_id = ? AND type = 'email'");
      $resetStmt->bind_param("si", $newEmail, $customerId);
      $resetStmt->execute();
      $resetStmt->close();

      echo json_encode(['success' => true, 'message' => 'Email updated successfully. Please verify your new email.']);
      break;

    case 'update_profile_picture':
      if (!isset($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'Please select a valid image file']);
        exit;
      }

      $file = $_FILES['profile_picture'];
      $maxSize = 2 * 1024 * 1024; // 2MB
      $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];

      // Validate file size
      if ($file['size'] > $maxSize) {
        echo json_encode(['success' => false, 'message' => 'File size must not exceed 2MB']);
        exit;
      }

      // Validate file type
      if (!in_array($file['type'], $allowedTypes)) {
        echo json_encode(['success' => false, 'message' => 'Only JPG, PNG, and GIF files are allowed']);
        exit;
      }

      // Create upload directory if not exists
      $uploadDir = __DIR__ . '/../../database/uploads/customers/';
      if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
      }

      // Generate unique filename
      $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
      $filename = 'customer_' . $customerId . '_' . uniqid() . '.' . $extension;
      $uploadPath = $uploadDir . $filename;
      $dbPath = '/PROGNET/database/uploads/customers/' . $filename;

      // Get old profile picture to delete
      $stmt = $conn->prepare("SELECT profile_picture FROM customers WHERE id = ?");
      $stmt->bind_param("i", $customerId);
      $stmt->execute();
      $result = $stmt->get_result();
      $customer = $result->fetch_assoc();
      $stmt->close();

      // Move uploaded file
      if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        // Delete old profile picture if exists
        if (!empty($customer['profile_picture']) && file_exists(__DIR__ . '/../../' . $customer['profile_picture'])) {
          @unlink(__DIR__ . '/../../' . $customer['profile_picture']);
        }

        // Update database
        $stmt = $conn->prepare("UPDATE customers SET profile_picture = ? WHERE id = ?");
        $stmt->bind_param("si", $dbPath, $customerId);
        $stmt->execute();
        $stmt->close();

        // Also update profile_path in product_reviews table for this customer (if column exists)
        $reviewStmt = $conn->prepare("UPDATE product_reviews SET customer_avatar = ? WHERE customer_id = ?");
        if ($reviewStmt) {
          $reviewStmt->bind_param("si", $dbPath, $customerId);
          if (!$reviewStmt->execute()) {
            // Log error but don't fail the profile picture update
            error_log('Failed to update product_reviews profile_path: ' . $reviewStmt->error);
          }
          $reviewStmt->close();
        }

        echo json_encode(['success' => true, 'message' => 'Profile picture updated successfully']);
      } else {
        echo json_encode(['success' => false, 'message' => 'Failed to upload file']);
      }
      break;

    case 'verify_email':
      $verificationCode = trim($_POST['verification_code'] ?? '');

      if (empty($verificationCode)) {
        echo json_encode(['success' => false, 'message' => 'Verification code is required']);
        exit;
      }

      if (!preg_match('/^\d{6}$/', $verificationCode)) {
        echo json_encode(['success' => false, 'message' => 'Verification code must be 6 digits']);
        exit;
      }

      // Get customer email
      $stmt = $conn->prepare("SELECT email FROM customers WHERE id = ?");
      $stmt->bind_param("i", $customerId);
      $stmt->execute();
      $result = $stmt->get_result();
      $customer = $result->fetch_assoc();
      $stmt->close();

      if (!$customer) {
        echo json_encode(['success' => false, 'message' => 'Customer not found']);
        exit;
      }

      $email = $customer['email'];

      // Get OTP from database
      $stmt = $conn->prepare("SELECT * FROM otp_verifications WHERE customer_id = ? AND type = 'email' AND target = ? ORDER BY created_at DESC LIMIT 1");
      $stmt->bind_param("is", $customerId, $email);
      $stmt->execute();
      $result = $stmt->get_result();
      $otpRecord = $result->fetch_assoc();
      $stmt->close();

      if (!$otpRecord) {
        echo json_encode(['success' => false, 'message' => 'No verification code found. Please request a new one.']);
        exit;
      }

      if (strtotime($otpRecord['expired_at']) < time()) {
        echo json_encode(['success' => false, 'message' => 'Verification code has expired. Please request a new one.']);
        exit;
      }

      if (!password_verify($verificationCode, $otpRecord['otp_hash'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid verification code.']);
        exit;
      }

      // Mark email as verified
      $verifiedAt = date('Y-m-d H:i:s');
      $stmt = $conn->prepare("UPDATE otp_verifications SET verified_at = ? WHERE id = ?");
      $stmt->bind_param("si", $verifiedAt, $otpRecord['id']);
      $stmt->execute();
      $stmt->close();

      echo json_encode(['success' => true, 'message' => 'Email verified successfully']);
      break;

    case 'resend_verification_code':
      // Get customer email
      $stmt = $conn->prepare("SELECT email FROM customers WHERE id = ?");
      $stmt->bind_param("i", $customerId);
      $stmt->execute();
      $result = $stmt->get_result();
      $customer = $result->fetch_assoc();
      $stmt->close();

      if (!$customer) {
        echo json_encode(['success' => false, 'message' => 'Customer not found']);
        exit;
      }

      $email = $customer['email'];

      // Generate new OTP
      $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
      $otpHash = password_hash($otp, PASSWORD_BCRYPT);
      $expiredAt = date('Y-m-d H:i:s', time() + 600); // 10 minutes
      $createdAt = date('Y-m-d H:i:s');

      // Check if OTP record already exists
      $checkStmt = $conn->prepare("SELECT id FROM otp_verifications WHERE customer_id = ? AND type = 'email' AND target = ? LIMIT 1");
      $checkStmt->bind_param("is", $customerId, $email);
      $checkStmt->execute();
      $checkResult = $checkStmt->get_result();
      $existingRecord = $checkResult->fetch_assoc();
      $checkStmt->close();

      if ($existingRecord) {
        // Update existing record
        $stmt = $conn->prepare("UPDATE otp_verifications SET otp_hash = ?, expired_at = ?, created_at = ?, verified_at = NULL WHERE customer_id = ? AND type = 'email' AND target = ?");
        $stmt->bind_param("sssis", $otpHash, $expiredAt, $createdAt, $customerId, $email);
        $stmt->execute();
        $stmt->close();
      } else {
        // Insert new record
        $stmt = $conn->prepare("INSERT INTO otp_verifications (customer_id, type, target, otp_hash, expired_at, created_at) VALUES (?, 'email', ?, ?, ?, ?)");
        $stmt->bind_param("issss", $customerId, $email, $otpHash, $expiredAt, $createdAt);
        $stmt->execute();
        $stmt->close();
      }

      // Send OTP via email using PHPMailer
      try {
        $mail = new PHPMailer(true);

        // Server settings
        $mail->isSMTP();
        $mail->Host = $_ENV['SMTP_HOST'] ?? '';
        $mail->SMTPAuth = true;
        $mail->Username = $_ENV['SMTP_USERNAME'] ?? '';
        $mail->Password = $_ENV['SMTP_PASSWORD'] ?? '';
        $mail->SMTPSecure = $_ENV['SMTP_SECURE'] ?? PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = (int) ($_ENV['SMTP_PORT'] ?? 587);

        // Recipients
        $fromEmail = $_ENV['SMTP_FROM'] ?? ($_ENV['SMTP_USERNAME'] ?? 'no-reply@example.com');
        $fromName = $_ENV['SMTP_FROM_NAME'] ?? 'uRoam Support';

        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($email);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Email Verification Code - uRoam';
        $mail->Body = '<p>Use this code to verify your email:</p><h2 style="letter-spacing:3px;">' . htmlspecialchars($otp) . '</h2><p>Valid for 10 minutes. If you did not request this, please ignore.</p>';
        $mail->AltBody = "Your email verification code: $otp (valid 10 minutes).";

        $mail->send();
        echo json_encode(['success' => true, 'message' => 'Verification code sent to your email']);
      } catch (Exception $e) {
        // On mail failure, clean inserted OTP to avoid stale entries
        $cleanupStmt = $conn->prepare("DELETE FROM otp_verifications WHERE customer_id = ? AND type = 'email' AND target = ?");
        $cleanupStmt->bind_param("is", $customerId, $email);
        $cleanupStmt->execute();
        $cleanupStmt->close();

        echo json_encode(['success' => false, 'message' => 'Failed to send email. Please try again.']);
      }
      break;

    case 'change_password':
      $currentPassword = $_POST['current_password'] ?? '';
      $newPassword = $_POST['new_password'] ?? '';
      $confirmPassword = $_POST['confirm_password'] ?? '';

      if (!$currentPassword || !$newPassword || !$confirmPassword) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit;
      }

      if ($newPassword !== $confirmPassword) {
        echo json_encode(['success' => false, 'message' => 'Passwords do not match']);
        exit;
      }

      $strongEnough = strlen($newPassword) >= 8 && preg_match('/[A-Z]/', $newPassword) && preg_match('/[a-z]/', $newPassword) && preg_match('/[^A-Za-z0-9]/', $newPassword);
      if (!$strongEnough) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters and include uppercase, lowercase, and symbol.']);
        exit;
      }

      if ($currentPassword === $newPassword) {
        echo json_encode(['success' => false, 'message' => 'New password must be different from current password']);
        exit;
      }

      // Fetch current password hash
      $stmt = $conn->prepare("SELECT password FROM customers WHERE id = ? LIMIT 1");
      $stmt->bind_param("i", $customerId);
      $stmt->execute();
      $result = $stmt->get_result();
      $customer = $result->fetch_assoc();
      $stmt->close();

      if (!$customer) {
        echo json_encode(['success' => false, 'message' => 'Customer not found']);
        exit;
      }

      if (!password_verify($currentPassword, $customer['password'])) {
        echo json_encode(['success' => false, 'message' => 'Wrong password']);
        exit;
      }

      $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
      $updateStmt = $conn->prepare("UPDATE customers SET password = ? WHERE id = ?");
      $updateStmt->bind_param("si", $newHash, $customerId);
      $updateStmt->execute();
      $updateStmt->close();

      echo json_encode(['success' => true, 'message' => 'Password updated successfully']);
      break;

    case 'delete_account':
      $confirmText = strtolower(trim($_POST['confirm_text'] ?? ''));

      if ($confirmText !== 'delete') {
        echo json_encode(['success' => false, 'message' => 'Confirmation text must be "delete"']);
        exit;
      }

      // Fetch profile picture path to delete file after account removal
      $stmt = $conn->prepare("SELECT profile_picture FROM customers WHERE id = ?");
      $stmt->bind_param("i", $customerId);
      $stmt->execute();
      $result = $stmt->get_result();
      $customer = $result->fetch_assoc();
      $stmt->close();

      if (!$customer) {
        echo json_encode(['success' => false, 'message' => 'Customer not found']);
        exit;
      }

      $profilePicPath = $customer['profile_picture'] ?? '';

      // Transaction to delete related data safely
      $conn->begin_transaction();
      try {
        // Delete OTP records for this customer
        $delOtp = $conn->prepare("DELETE FROM otp_verifications WHERE customer_id = ?");
        $delOtp->bind_param("i", $customerId);
        $delOtp->execute();
        $delOtp->close();

        // Delete customer record
        $delCustomer = $conn->prepare("DELETE FROM customers WHERE id = ?");
        $delCustomer->bind_param("i", $customerId);
        $delCustomer->execute();
        $delCustomer->close();

        $conn->commit();
      } catch (Exception $txEx) {
        $conn->rollback();
        throw $txEx;
      }

      // Delete profile picture file if exists and stored path is present
      if (!empty($profilePicPath)) {
        $absPath = __DIR__ . '/../../' . ltrim($profilePicPath, '/');
        if (file_exists($absPath)) {
          @unlink($absPath);
        }
      }

      // Destroy session
      session_unset();
      session_destroy();

      echo json_encode(['success' => true, 'message' => 'Account deleted successfully']);
      break;

    default:
      echo json_encode(['success' => false, 'message' => 'Invalid action']);
      break;
  }
} catch (Exception $e) {
  echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}
