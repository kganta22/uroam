<?php
/* Customer Login Page */
require_once __DIR__ . '/../_guards/guestGuard.php';
require_once __DIR__ . '/../../database/connect.php';

$emailError = '';
$passwordError = '';
$emailValue = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';
  $emailValue = htmlspecialchars($email);

  if ($email && $password) {
    // Check if email exists
    $stmt = $conn->prepare("SELECT id, full_name, email, password FROM customers WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
      $user = $result->fetch_assoc();

      // Verify password
      if (password_verify($password, $user['password'])) {
        // Login successful
        $_SESSION['customer_id'] = $user['id'];
        $_SESSION['customer_name'] = $user['full_name'];
        $_SESSION['customer_email'] = $user['email'];

        // Redirect to homepage or dashboard
        header('Location: /PROGNET/customer/tours.php');
        exit;
      } else {
        // Incorrect password
        $passwordError = 'Incorrect password';
      }
    } else {
      // Email not found
      $emailError = 'No account with that email was found';
    }
    $stmt->close();
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Log In - uRoam</title>

  <link rel="icon" type="image/uroam-icon" href="/PROGNET/images/uroam-title.ico">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Solway:wght@300;400;500;700;800&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="/PROGNET/assets/shared/css/style.css">
  <link rel="stylesheet" href="/PROGNET/assets/admin/css/sidebarAdmin.css">
  <link rel="stylesheet" href="/PROGNET/assets/customer/css/navbar.css">
  <link rel="stylesheet" href="/PROGNET/assets/customer/css/footer.css">
  <link rel="stylesheet" href="/PROGNET/assets/customer/css/login.css">
</head>

<body>
  <!-- Sidebar -->
  <?php require_once __DIR__ . '/../_partials/sidebarCustomer.html'; ?>

  <!-- Navbar -->
  <?php require_once __DIR__ . '/../_partials/navbar.php'; ?>

  <div class="content-wrapper">
    <main class="login-section">
      <div class="login-container">
        <div class="login-card">
          <h1 class="login-title">Log In</h1>

          <form class="login-form" action="#" method="POST">
            <div class="form-group">
              <label for="email" class="form-label">Email</label>
              <input type="email" id="email" name="email" class="form-input" placeholder="Email"
                value="<?= $emailValue ?>" required />
              <?php if ($emailError): ?>
                <span class="error-message"><?= $emailError ?></span>
              <?php endif; ?>
            </div>

            <div class="form-group">
              <label for="password" class="form-label">Password</label>
              <div class="password-wrapper">
                <input type="password" id="password" name="password" class="form-input" placeholder="Password"
                  required />
                <button type="button" class="toggle-password" aria-label="Toggle password visibility">
                  <img src="/PROGNET/images/icons/eye.svg" alt="Show password" class="eye-icon">
                </button>
              </div>
              <?php if ($passwordError): ?>
                <span class="error-message"><?= $passwordError ?></span>
              <?php endif; ?>
            </div>

            <div class="form-terms">
              <p>By signing in, I agree to the <a href="/PROGNET/customer/terms.php" class="terms-link">Terms &
                  conditions</a> and the <a href="/PROGNET/customer/policy.php" class="terms-link">privacy policy</a> of
                uRoam.</p>
            </div>

            <button type="submit" class="btn-login">Log In</button>

            <a href="/PROGNET/customer/auth/forgot-password.php" class="forgot-link">Forgot password</a>

            <div class="divider">
              <span>or</span>
            </div>

            <button type="button" class="btn-create" onclick="window.location.href='signup.php'">
              Create account
            </button>
          </form>
        </div>
      </div>
    </main>
  </div>

  <!-- Footer -->
  <?php require_once __DIR__ . '/../_partials/footer.php'; ?>

  <script src="/PROGNET/assets/admin/js/sidebarAdmin.js"></script>
  <script src="/PROGNET/assets/customer/js/login.js"></script>
</body>

</html>