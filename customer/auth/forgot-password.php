<?php
/* Customer Forgot Password Page */
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
require_once __DIR__ . '/../../database/connect.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Dotenv\Dotenv;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$dotenv = Dotenv::createImmutable(dirname(__DIR__, 2));
$dotenv->load();

$submittedEmail = '';
$otpType = 'email';
$showVerify = false;
$resetError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'send_otp') {
  $submittedEmail = trim($_POST['email'] ?? '');
  $showVerify = true; // Selalu lanjut ke step verifikasi tanpa notifikasi

  if ($submittedEmail && filter_var($submittedEmail, FILTER_VALIDATE_EMAIL)) {
    // Find user
    $findQuery = "SELECT id, full_name FROM customers WHERE email = ? LIMIT 1";
    $findStmt = $conn->prepare($findQuery);
    $findStmt->bind_param('s', $submittedEmail);
    $findStmt->execute();
    $userResult = $findStmt->get_result();
    $user = $userResult->fetch_assoc();
    $findStmt->close();

    if ($user) {
      // Generate OTP
      $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
      $otpHash = password_hash($otp, PASSWORD_BCRYPT);
      $expiredAt = date('Y-m-d H:i:s', time() + 600);
      $createdAt = date('Y-m-d H:i:s');

      // Check if email already exists
      $checkQuery = "SELECT id FROM otp_verifications WHERE target = ? AND type = ? LIMIT 1";
      $checkStmt = $conn->prepare($checkQuery);
      $checkStmt->bind_param('ss', $submittedEmail, $otpType);
      $checkStmt->execute();
      $checkResult = $checkStmt->get_result();
      $existingOtp = $checkResult->fetch_assoc();
      $checkStmt->close();

      if ($existingOtp) {
        // Update existing OTP
        $updateQuery = "UPDATE otp_verifications SET otp_hash = ?, expired_at = ?, verified_at = NULL, created_at = ? WHERE target = ? AND type = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param('sssss', $otpHash, $expiredAt, $createdAt, $submittedEmail, $otpType);
        $updateStmt->execute();
        $updateStmt->close();
      } else {
        // Insert new OTP
        $insertQuery = "INSERT INTO otp_verifications (customer_id, type, target, otp_hash, expired_at, created_at) VALUES (?, ?, ?, ?, ?, ?)";
        $insertStmt = $conn->prepare($insertQuery);
        $insertStmt->bind_param('isssss', $user['id'], $otpType, $submittedEmail, $otpHash, $expiredAt, $createdAt);
        $insertStmt->execute();
        $insertStmt->close();
      }

      // Send email
      $mail = new PHPMailer(true);
      try {
        $mail->isSMTP();
        $mail->Host = $_ENV['SMTP_HOST'] ?? '';
        $mail->SMTPAuth = true;
        $mail->Username = $_ENV['SMTP_USERNAME'] ?? '';
        $mail->Password = $_ENV['SMTP_PASSWORD'] ?? '';
        $mail->SMTPSecure = $_ENV['SMTP_SECURE'] ?? PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = (int) ($_ENV['SMTP_PORT'] ?? 587);

        $fromEmail = $_ENV['SMTP_FROM'] ?? ($_ENV['SMTP_USERNAME'] ?? 'no-reply@example.com');
        $fromName = $_ENV['SMTP_FROM_NAME'] ?? 'uRoam Support';

        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($submittedEmail, $user['full_name'] ?? '');

        $mail->isHTML(true);
        $mail->Subject = 'Your uRoam password reset code';
        $mail->Body = '<p>Use this code to reset your password:</p><h2 style="letter-spacing:3px;">' . htmlspecialchars($otp) . '</h2><p>Valid for 10 minutes. If you did not request this, please ignore.</p>';
        $mail->AltBody = "Your uRoam password reset code: $otp (valid 10 minutes).";

        $mail->send();
      } catch (Exception $e) {
        // On mail failure, nothing to cleanup (data already stored)
      }
    }
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reset_password') {
  $submittedEmail = trim($_POST['email'] ?? '');
  $showVerify = true; // tetap di step verifikasi bila gagal

  $code = trim($_POST['code'] ?? '');
  $newPassword = $_POST['new-password'] ?? '';
  $confirmPassword = $_POST['confirm-password'] ?? '';

  if (!$submittedEmail || !filter_var($submittedEmail, FILTER_VALIDATE_EMAIL)) {
    $resetError = 'Invalid email address.';
  } elseif (!$code || !$newPassword || !$confirmPassword) {
    $resetError = 'All fields are required.';
  } elseif ($newPassword !== $confirmPassword) {
    $resetError = 'Passwords do not match.';
  } elseif (strlen($newPassword) < 8 || !preg_match('/[A-Z]/', $newPassword) || !preg_match('/[a-z]/', $newPassword) || !preg_match('/[^A-Za-z0-9]/', $newPassword)) {
    $resetError = 'Password must be at least 8 characters and include uppercase, lowercase, and symbol.';
  }

  if (!$resetError) {
    // Pastikan user ada
    $userQuery = "SELECT id FROM customers WHERE email = ? LIMIT 1";
    $userStmt = $conn->prepare($userQuery);
    $userStmt->bind_param('s', $submittedEmail);
    $userStmt->execute();
    $userResult = $userStmt->get_result();
    $user = $userResult->fetch_assoc();
    $userStmt->close();

    if (!$user) {
      // Samarkan detail agar tidak bocor apakah email ada
      $resetError = 'Invalid or expired verification code.';
    }
  }

  if (!$resetError) {
    // Ambil OTP terbaru
    $otpQuery = "SELECT id, otp_hash, expired_at FROM otp_verifications WHERE target = ? AND type = ? ORDER BY created_at DESC LIMIT 1";
    $otpStmt = $conn->prepare($otpQuery);
    $otpStmt->bind_param('ss', $submittedEmail, $otpType);
    $otpStmt->execute();
    $otpResult = $otpStmt->get_result();
    $otpRow = $otpResult->fetch_assoc();
    $otpStmt->close();

    if (!$otpRow) {
      $resetError = 'Invalid or expired verification code.';
    } else {
      $isExpired = strtotime($otpRow['expired_at']) < time();
      $isMatch = password_verify($code, $otpRow['otp_hash']);
      if ($isExpired || !$isMatch) {
        $resetError = 'Invalid or expired verification code.';
      }
    }
  }

  if (!$resetError) {
    $newPasswordHash = password_hash($newPassword, PASSWORD_BCRYPT);
    $updateQuery = "UPDATE customers SET password = ? WHERE email = ?";
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bind_param('ss', $newPasswordHash, $submittedEmail);
    $updateStmt->execute();
    $updateStmt->close();

    // Mark OTP as verified
    if (isset($otpRow['id'])) {
      $verifiedAt = date('Y-m-d H:i:s');
      $verifyQuery = "UPDATE otp_verifications SET verified_at = ? WHERE id = ?";
      $verifyStmt = $conn->prepare($verifyQuery);
      $verifyStmt->bind_param('si', $verifiedAt, $otpRow['id']);
      $verifyStmt->execute();
      $verifyStmt->close();
    }

    header('Location: /PROGNET/customer/auth/login.php?reset=success');
    exit();
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Forgot Password - uRoam</title>

  <link rel="icon" type="image/uroam-icon" href="/PROGNET/images/uroam-title.ico">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Solway:wght@300;400;500;700;800&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="/PROGNET/assets/shared/css/style.css">
  <link rel="stylesheet" href="/PROGNET/assets/admin/css/sidebarAdmin.css">
  <link rel="stylesheet" href="/PROGNET/assets/customer/css/navbar.css">
  <link rel="stylesheet" href="/PROGNET/assets/customer/css/footer.css">
  <link rel="stylesheet" href="/PROGNET/assets/customer/css/login.css">
  <link rel="stylesheet" href="/PROGNET/assets/customer/css/forgot-password.css">
</head>

<body>
  <!-- Navbar -->
  <!-- Sidebar -->
  <?php require_once __DIR__ . '/../_partials/sidebarCustomer.html'; ?>

  <?php require_once __DIR__ . '/../_partials/navbar.php'; ?>

  <div class="content-wrapper">
    <main class="forgot-section">
      <div class="forgot-container">
        <div class="forgot-card">

          <?php $backHref = $showVerify ? '/PROGNET/customer/auth/forgot-password.php' : '/PROGNET/customer/auth/login.php'; ?>
          <div class="signup-header">
            <button type="button" class="back-button" onclick="window.location.href='<?= $backHref ?>'"
              aria-label="Back">
              <img src="/PROGNET/images/icons/arrow-left.svg" alt="Back" width="24" height="24">
            </button>
            <h1 class="login-title">Forgot password</h1>
          </div>

          <!-- Step 1: Email Input -->
          <?php $emailStepClass = $showVerify ? 'forgot-step is-hidden' : 'forgot-step'; ?>
          <div class="<?= $emailStepClass ?>" id="step-email">
            <p class="forgot-description">
              Please enter your email to reset your password. You will receive a reset code to create new password via
              email.
            </p>

            <form class="forgot-form" id="form-email" method="POST"
              action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
              <input type="hidden" name="action" value="send_otp">
              <div class="form-group">
                <label for="email" class="form-label">Email</label>
                <input type="email" id="email" name="email" class="form-input" placeholder="email@email.com"
                  value="<?= htmlspecialchars($submittedEmail) ?>" required />
              </div>

              <button type="submit" class="btn-continue">Continue</button>
            </form>
          </div>

          <!-- Step 2: Verification & Password Reset -->
          <?php $verifyStepClass = $showVerify ? 'forgot-step' : 'forgot-step is-hidden'; ?>
          <div class="<?= $verifyStepClass ?>" id="step-verify">
            <p class="forgot-description" id="verify-message">
              Verification code has been sent to <strong
                id="email-display"><?= htmlspecialchars($submittedEmail ?: 'user@email.com') ?></strong>. Please check
              your email.
            </p>

            <?php if ($resetError): ?>
              <div class="error-message" id="verify-error"
                style="margin-bottom: 20px; padding: 12px; background: #fee; border: 1px solid #fcc; border-radius: 8px; color: #c0392b;">
                <?= htmlspecialchars($resetError) ?>
              </div>
            <?php else: ?>
              <div class="error-message" id="verify-error" hidden
                style="margin-bottom: 20px; padding: 12px; background: #fee; border: 1px solid #fcc; border-radius: 8px; color: #c0392b;">
              </div>
            <?php endif; ?>

            <form class="forgot-form" id="form-verify" method="POST"
              action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
              <input type="hidden" name="action" value="reset_password">
              <input type="hidden" name="email" value="<?= htmlspecialchars($submittedEmail) ?>">

              <div class="form-group">
                <label for="code" class="form-label">Verification code</label>
                <input type="text" id="code" name="code" class="form-input" placeholder="Verification code" required />
              </div>

              <div class="form-group">
                <label for="new-password" class="form-label">New password</label>
                <div class="password-wrapper">
                  <input type="password" id="new-password" name="new-password" class="form-input" placeholder="Password"
                    required />
                  <button type="button" class="toggle-password" aria-label="Toggle password visibility">
                    <img src="/PROGNET/images/icons/eye.svg" alt="Show password" class="eye-icon">
                  </button>
                </div>
                <div class="pwd-hint" id="pwd-hint" hidden>
                  <p class="pwd-hint-title">Password must have:</p>
                  <ul class="pwd-criteria">
                    <li class="criteria-item" data-criteria="length">At least 8 characters</li>
                    <li class="criteria-item" data-criteria="upper">At least 1 uppercase letter</li>
                    <li class="criteria-item" data-criteria="lower">At least 1 lowercase letter</li>
                    <li class="criteria-item" data-criteria="symbol">At least 1 symbol</li>
                  </ul>
                </div>
              </div>

              <div class="form-group">
                <label for="confirm-password" class="form-label">Confirm new password</label>
                <div class="password-wrapper">
                  <input type="password" id="confirm-password" name="confirm-password" class="form-input"
                    placeholder="Confirm password" required />
                  <button type="button" class="toggle-password-confirm" aria-label="Toggle password visibility">
                    <img src="/PROGNET/images/icons/eye.svg" alt="Show password" class="eye-icon">
                  </button>
                </div>
                <div class="error-message" id="confirm-error" hidden
                  style="margin-top: 8px; padding: 10px; background: #fee; border: 1px solid #fcc; border-radius: 8px; color: #c0392b;">
                  Passwords do not match</div>
              </div>

              <button type="submit" class="btn-continue">Continue</button>
            </form>
          </div>

        </div>
      </div>
    </main>
  </div>

  <!-- Footer -->
  <?php require_once __DIR__ . '/../_partials/footer.php'; ?>

  <script src="/PROGNET/assets/admin/js/sidebarAdmin.js"></script>
  <script src="/PROGNET/assets/customer/js/forgot-password.js"></script>
</body>

</html>