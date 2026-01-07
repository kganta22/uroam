<?php
session_start();

require_once __DIR__ . '/../../database/connect.php';

if (isset($_SESSION['admin_id'])) {
  header("Location: /PROGNET/admin/home.php");
  exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $email = $_POST['email'];
  $password = $_POST['password'];

  $stmt = $conn->prepare("SELECT id, full_name, email, password, role FROM admin WHERE email = ?");
  $stmt->bind_param("s", $email);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();

    // Verifikasi password (password_hash)
    if (password_verify($password, $user['password'])) {

      // Set session
      $_SESSION['admin_id'] = $user['id'];
      $_SESSION['admin_name'] = $user['full_name'];
      $_SESSION['admin_email'] = $user['email'];
      $_SESSION['role'] = $user['role'];


      // Redirect ke home
      header("Location: /PROGNET/admin/home.php");
      exit;
    } else {
      $error = "Password salah!";
    }
  } else {
    $error = "Email tidak ditemukan!";
  }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Log in — Supplier Portal</title>

  <link rel="icon" type="image/uroam-icon" href="/PROGNET/images/uroam-title.ico" />

  <!-- GANTI DI SINI (FONT bila perlu) -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Solway:wght@300;400;500;700;800&display=swap" rel="stylesheet" />

  <link rel="stylesheet" href="/PROGNET/assets/shared/css/style.css" />
  <link rel="stylesheet" href="/PROGNET/assets/admin/css/login.css" />
</head>

<body class="lg">
  <!-- HEADER -->
  <?php include '../_partials/navbarAdminLogin.html'; ?>

  <main class="lg-main">
    <!-- Area gradient (atur di CSS) -->
    <section class="lg-hero">
      <!-- Kartu form -->
      <section class="lg-card" aria-labelledby="loginTitle">
        <h1 id="loginTitle" class="lg-title">Log in to the Admin Portal</h1>

        <?php if (!empty($error)): ?>
          <div class="lg-alert">
            <?= $error ?>
          </div>
        <?php endif; ?>

        <form class="lg-form" action="login.php" method="post">
          <!-- Email -->
          <div class="lg-field">
            <label for="email" class="lg-label">
              Email<span aria-hidden="true" class="lg-req">*</span>
            </label>
            <input id="email" name="email" type="email" autocomplete="username" required class="lg-input" />
          </div>

          <!-- Password -->
          <div class="lg-field">
            <label for="password" class="lg-label">
              Password<span aria-hidden="true" class="lg-req">*</span>
            </label>
            <input id="password" name="password" type="password" autocomplete="current-password" required
              class="lg-input" />
          </div>

          <!-- Submit -->
          <div class="lg-actions">
            <button type="submit" class="lg-btn lg-btn--primary">
              Log in
            </button>
          </div>
        </form>
      </section>
    </section>
  </main>
</body>

</html>