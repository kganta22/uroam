<?php
/* Customer Contact Page */
require_once __DIR__ . '/../database/connect.php';

$contactPhone = '';
$contactEmail = '';

$stmt = $conn->prepare("SELECT customer_service_phone, customer_service_email FROM company_profile WHERE id = 1 LIMIT 1");
if ($stmt) {
  $stmt->execute();
  $res = $stmt->get_result();
  if ($row = $res->fetch_assoc()) {
    $contactPhone = $row['customer_service_phone'] ?? '';
    $contactEmail = $row['customer_service_email'] ?? '';
  }
  $stmt->close();
}

$phoneHref = $contactPhone !== '' ? $contactPhone : '#';
$emailHref = $contactEmail !== '' ? 'mailto:' . $contactEmail : '#';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Contact Us - uRoam</title>

  <link rel="icon" type="image/uroam-icon" href="/PROGNET/images/uroam-title.ico">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Solway:wght@300;400;500;700;800&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="/PROGNET/assets/shared/css/style.css">
  <link rel="stylesheet" href="/PROGNET/assets/admin/css/sidebarAdmin.css">
  <link rel="stylesheet" href="/PROGNET/assets/customer/css/navbar.css">
  <link rel="stylesheet" href="/PROGNET/assets/customer/css/footer.css">
  <link rel="stylesheet" href="/PROGNET/assets/customer/css/contact.css">
</head>

<body>
  <!-- Sidebar -->
  <?php require_once __DIR__ . '/_partials/sidebarCustomer.html'; ?>

  <!-- Navbar -->
  <?php require_once __DIR__ . '/_partials/navbar.php'; ?>
  <div class="content-wrapper">
    <main class="contact-section">
      <!-- Hero / Heading -->
      <section class="contact-header">
        <h1 class="contact-heading">Hi, we're happy to help you.</h1>
      </section>

      <!-- Info + Form -->
      <section class="contact-content">
        <!-- Contact Info -->
        <div class="contact-info">
          <div class="contact-item">
            <img src="/PROGNET/images/email-icon.png" alt="Email" class="contact-icon" />
            <a href="<?= htmlspecialchars($emailHref, ENT_QUOTES) ?>" class="contact-link">
              <?= htmlspecialchars($contactEmail) ?>
            </a>
          </div>
          <div class="contact-item">
            <img src="/PROGNET/images/phone-icon.png" alt="Phone" class="contact-icon" />
            <a href="https://wa.me/<?= htmlspecialchars($phoneHref, ENT_QUOTES) ?>" class="contact-link">
              <?= htmlspecialchars($contactPhone) ?>
            </a>
          </div>
        </div>

        <!-- Contact Form -->
        <form class="contact-form" action="#" method="POST" novalidate>
          <div class="form-grid">
            <div class="form-group">
              <label for="name" class="form-label">Name</label>
              <input type="text" id="name" name="name" class="form-input" required />
            </div>

            <div class="form-group">
              <label for="email" class="form-label">Email</label>
              <input type="email" id="email" name="email" class="form-input" required />
            </div>

            <div class="form-group full">
              <label for="question" class="form-label">What's your question about?</label>
              <div class="select-wrap">
                <select id="question" name="question" class="form-select" required>
                  <option value="">Select a topic</option>
                  <option value="booking">Booking</option>
                  <option value="payment">Payment</option>
                  <option value="technical">Technical Support</option>
                  <option value="other">Other</option>
                </select>
              </div>
            </div>

            <div class="form-group full">
              <label for="message" class="form-label">Message</label>
              <textarea id="message" name="message" class="form-textarea" rows="8" required></textarea>
            </div>

            <div class="form-actions">
              <button type="submit" class="submit-btn">Get a Solution</button>
            </div>
          </div>
        </form>
      </section>
    </main>
  </div>
  <!-- Footer -->
  <?php require_once __DIR__ . '/_partials/footer.php'; ?>
  <script src="/PROGNET/assets/admin/js/sidebarAdmin.js"></script>
</body>

</html>