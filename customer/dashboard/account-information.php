<?php
/* Customer Account Information Page */
require_once __DIR__ . '/../_guards/customerGuard.php';
require_once __DIR__ . '/../../database/connect.php';

$customerId = $_SESSION['customer_id'];
$customerName = '';
$customerEmail = '';
$customerPhone = '';
$firstName = '';
$lastName = '';
$country = '';
$point = 0;
$usedPoints = 0;
$totalPoints = 0;
$profilePicture = '/PROGNET/images/icons/no-profile.png';

// Fetch customer data
$stmt = $conn->prepare("SELECT full_name, first_name, last_name, email, phone, country, profile_picture, point FROM customers WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $customerId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
  $customer = $result->fetch_assoc();
  $customerName = $customer['full_name'] ?? '';
  $firstName = $customer['first_name'] ?? '';
  $lastName = $customer['last_name'] ?? '';
  $customerEmail = $customer['email'] ?? '';
  $customerPhone = $customer['phone'] ?? '';
  $country = $customer['country'] ?? '';
  $point = $customer['point'] ?? 0;
  // Initialize totals with active points; used points will be added below.
  $totalPoints = $point;

  if (!empty($customer['profile_picture'])) {
    $profilePicture = $customer['profile_picture'];
  }
}
$stmt->close();

// Calculate used points from historical discounts (assumes discount_rate records point usage in currency)
$usedStmt = $conn->prepare("SELECT COALESCE(SUM(discount_rate), 0) AS total_discount FROM bookings WHERE customer_id = ?");
if ($usedStmt) {
  $usedStmt->bind_param("i", $customerId);
  if ($usedStmt->execute()) {
    $usedResult = $usedStmt->get_result();
    if ($usedRow = $usedResult->fetch_assoc()) {
      // Treat discount_rate as already recorded in points
      $usedPoints = (int) floor((float) ($usedRow['total_discount'] ?? 0));
      $totalPoints = $point + $usedPoints;
    }
  }
  $usedStmt->close();
}

// Check verification status from otp_verifications table
$phoneVerified = false;
$emailVerified = false;

// Check phone verification
if ($customerPhone) {
  $stmt = $conn->prepare("SELECT verified_at FROM otp_verifications WHERE customer_id = ? AND type = 'phone' AND target = ? AND verified_at IS NOT NULL LIMIT 1");
  $stmt->bind_param("is", $customerId, $customerPhone);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result->num_rows > 0) {
    $phoneVerified = true;
  }
  $stmt->close();
}

// Check email verification
if ($customerEmail) {
  $stmt = $conn->prepare("SELECT verified_at FROM otp_verifications WHERE customer_id = ? AND type = 'email' AND target = ? AND verified_at IS NOT NULL LIMIT 1");
  $stmt->bind_param("is", $customerId, $customerEmail);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result->num_rows > 0) {
    $emailVerified = true;
  }
  $stmt->close();
}

// Determine icon paths
$phoneIcon = $phoneVerified ? '/PROGNET/images/icons/check-orange.svg' : '/PROGNET/images/icons/error-fill.svg';
$emailIcon = $emailVerified ? '/PROGNET/images/icons/check-orange.svg' : '/PROGNET/images/icons/error-fill.svg';

// Determine status text
$phoneStatus = $phoneVerified ? 'Confirmed' : 'Not Confirmed';
$emailStatus = $emailVerified ? 'Confirmed' : 'Not Confirmed';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Account Information - uRoam</title>

  <link rel="icon" type="image/uroam-icon" href="/PROGNET/images/uroam-title.ico">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Solway:wght@300;400;500;700;800&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="/PROGNET/assets/shared/css/style.css">
  <link rel="stylesheet" href="/PROGNET/assets/admin/css/sidebarAdmin.css">
  <link rel="stylesheet" href="/PROGNET/assets/customer/css/navbar.css">
  <link rel="stylesheet" href="/PROGNET/assets/customer/css/footer.css">
  <link rel="stylesheet" href="/PROGNET/assets/customer/css/account-information.css">
  <link rel="stylesheet" href="/PROGNET/assets/customer/css/custom-alert.css">
</head>

<body>
  <!-- Sidebar -->
  <?php require_once __DIR__ . '/../_partials/sidebarCustomer.html'; ?>

  <!-- Navbar -->
  <?php require_once __DIR__ . '/../_partials/navbar.php'; ?>

  <div class="content-wrapper">
    <main class="account-section">
      <div class="account-container">

        <!-- Profile Header Card -->
        <div class="profile-header-card">
          <div class="profile-header-content">
            <img src="<?= htmlspecialchars($profilePicture) ?>" alt="Profile" class="profile-avatar">
            <div class="profile-header-info">
              <h2 class="profile-name"><?= htmlspecialchars($customerName ?: 'User') ?></h2>
              <div class="profile-contact">
                <span class="contact-item">
                  +<?= htmlspecialchars($customerPhone) ?>
                  <?php if ($customerPhone): ?>
                    <img src="<?= htmlspecialchars($phoneIcon) ?>" alt="<?= htmlspecialchars($phoneStatus) ?>"
                      class="status-icon">
                  <?php endif; ?>
                </span>
                <span class="contact-item">
                  <?= htmlspecialchars($customerEmail) ?>
                  <?php if ($customerEmail): ?>
                    <img src="<?= htmlspecialchars($emailIcon) ?>" alt="<?= htmlspecialchars($emailStatus) ?>"
                      class="status-icon">
                  <?php endif; ?>
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Points Card -->
        <div class="points-card">
          <div class="points-card-content">
            <img src="/PROGNET/images/icons/uRoam-coin.png" alt="Coin" class="points-coin-icon">
            <div class="points-info">
              <span class="points-label"><?= htmlspecialchars($point) ?></span>
              <span class="points-text">Active points</span>
            </div>
          </div>
          <div class="points-footer">
            <span class="points-name">Used points: <?= htmlspecialchars($usedPoints) ?></span>
            <span class="points-name points-total">Total points: <?= htmlspecialchars($totalPoints) ?></span>
          </div>
        </div>

        <!-- Account Information Card -->
        <div class="info-card">
          <div class="info-card-header">
            <img src="/PROGNET/images/icons/info-circle.svg" alt="" class="header-icon">
            <h3 class="info-card-title">Account information</h3>
          </div>

          <div class="info-card-body">
            <!-- Phone Number -->
            <div class="info-group">
              <div class="info-label-row">
                <label class="info-label">Phone number</label>
                <span
                  class="<?= $phoneVerified ? 'status-confirmed' : 'status-not-confirmed' ?>"><?= htmlspecialchars($phoneStatus) ?></span>
              </div>
              <input type="text" class="info-input disabled" value="<?= htmlspecialchars($customerPhone) ?>" disabled>
            </div>

            <!-- Email -->
            <div class="info-group">
              <div class="info-label-row">
                <label class="info-label">Email</label>
                <span
                  class="<?= $emailVerified ? 'status-confirmed' : 'status-not-confirmed' ?>"><?= htmlspecialchars($emailStatus) ?></span>
              </div>
              <input type="text" class="info-input disabled" value="<?= htmlspecialchars($customerEmail) ?>" disabled>
              <div class="info-button-row">
                <?php if (!$emailVerified): ?>
                  <button type="button" class="btn-verify" onclick="openModal('verifyEmailModal')">Verify My
                    Email</button>
                <?php endif; ?>
                <a href="#" class="edit-link" onclick="openModal('editEmailModal'); return false;">Change my email</a>
              </div>
            </div>

            <!-- First Name -->
            <div class="info-group">
              <div class="info-label-row">
                <label class="info-label">First name</label>
                <a href="#" class="edit-link" onclick="openModal('editFirstNameModal'); return false;">Edit</a>
              </div>
              <div class="info-value"><?= htmlspecialchars($firstName ?: '-') ?></div>
            </div>

            <!-- Last Name -->
            <div class="info-group">
              <div class="info-label-row">
                <label class="info-label">Last name</label>
                <a href="#" class="edit-link" onclick="openModal('editLastNameModal'); return false;">Edit</a>
              </div>
              <div class="info-value"><?= htmlspecialchars($lastName ?: '-') ?></div>
            </div>

            <!-- Country -->
            <div class="info-group">
              <div class="info-label-row">
                <label class="info-label">Country</label>
              </div>
              <div class="info-value"><?= htmlspecialchars($country ?: '-') ?></div>
            </div>
          </div>
        </div>

        <!-- Profile Picture Card -->
        <div class="info-card">
          <div class="info-card-header">
            <img src="/PROGNET/images/icons/image.svg" alt="" class="header-icon">
            <h3 class="info-card-title">Profile picture</h3>
            <a href="#" class="edit-link ml-auto" id="editProfilePictureBtn"
              onclick="toggleEditProfilePicture(); return false;">Edit</a>
          </div>

          <div class="info-card-body profile-picture-body" id="profilePictureView">
            <div class="profile-picture-preview">
              <img src="<?= htmlspecialchars($profilePicture) ?>" alt="Profile" class="profile-picture-img">
            </div>
          </div>

          <!-- Profile Picture Upload Area (Hidden by default) -->
          <div class="info-card-body profile-picture-edit hidden" id="profilePictureEdit">
            <form id="editProfilePictureForm" method="POST" action="" enctype="multipart/form-data"
              class="form-full-width">
              <input type="hidden" name="action" value="update_profile_picture">
              <div class="upload-area" id="uploadArea">
                <svg class="upload-icon" viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="2">
                  <rect x="8" y="24" width="48" height="32" rx="2"></rect>
                  <circle cx="28" cy="32" r="4"></circle>
                  <path d="M56 24V16a2 2 0 0 0-2-2H10a2 2 0 0 0-2 2v8"></path>
                  <path d="M32 16v16"></path>
                  <path d="M28 12v8"></path>
                  <path d="M36 12v8"></path>
                </svg>
                <p class="upload-text">Upload image</p>
              </div>
              <input type="file" id="editProfilePicture" name="profile_picture" class="file-input-hidden"
                accept="image/*" required>
              <div class="file-info file-info-upload">Maximum file size: 2MB. Accepted formats: JPG, PNG, GIF</div>
              <div class="modal-actions modal-actions-with-margin">
                <button type="button" class="btn-cancel" onclick="toggleEditProfilePicture()">Cancel</button>
                <button type="submit" class="btn-save">Save</button>
              </div>
            </form>
          </div>
        </div>

        <!-- Delete Account Button -->
        <div class="delete-account-section">
          <button type="button" class="btn-delete-account" id="deleteAccountBtn">Delete account</button>
        </div>

      </div>
    </main>
  </div>

  <!-- Footer -->
  <?php require_once __DIR__ . '/../_partials/footer.php'; ?>

  <!-- Edit Modals -->
  <!-- First Name Modal -->
  <div id="editFirstNameModal" class="edit-modal">
    <div class="modal-overlay"></div>
    <div class="modal-content">
      <h3 class="modal-title">First name</h3>
      <form id="editFirstNameForm" method="POST" action="">
        <input type="hidden" name="action" value="update_first_name">
        <div class="modal-form-group">
          <input type="text" id="editFirstName" name="first_name" class="modal-input"
            value="<?= htmlspecialchars($firstName) ?>" required>
        </div>
        <div class="modal-actions">
          <button type="button" class="btn-cancel" onclick="closeModal('editFirstNameModal')">Cancel</button>
          <button type="submit" class="btn-save">Save</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Last Name Modal -->
  <div id="editLastNameModal" class="edit-modal">
    <div class="modal-overlay"></div>
    <div class="modal-content">
      <h3 class="modal-title">Last name</h3>
      <form id="editLastNameForm" method="POST" action="">
        <input type="hidden" name="action" value="update_last_name">
        <div class="modal-form-group">
          <input type="text" id="editLastName" name="last_name" class="modal-input"
            value="<?= htmlspecialchars($lastName) ?>" required>
        </div>
        <div class="modal-actions">
          <button type="button" class="btn-cancel" onclick="closeModal('editLastNameModal')">Cancel</button>
          <button type="submit" class="btn-save">Save</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Verify Email Modal -->
  <div id="verifyEmailModal" class="edit-modal">
    <div class="modal-overlay"></div>
    <div class="modal-content">
      <h3 class="modal-title">Verify Email</h3>
      <p class="verify-message">Enter the code we sent to <strong><?= htmlspecialchars($customerEmail) ?></strong></p>
      <form id="verifyEmailForm" method="POST" action="">
        <input type="hidden" name="action" value="verify_email">
        <div class="modal-form-group">
          <input type="text" id="verificationCode" name="verification_code" class="modal-input" placeholder="000000"
            maxlength="6" required>
        </div>
        <button type="button" class="btn-resend-code" id="resendCodeBtn" onclick="resendVerificationCode()">Resend
          code</button>
        <div class="modal-actions">
          <button type="button" class="btn-cancel" onclick="closeModal('verifyEmailModal')">Cancel</button>
          <button type="submit" class="btn-save">Verify</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Edit Email Modal -->
  <div id="editEmailModal" class="edit-modal">
    <div class="modal-overlay"></div>
    <div class="modal-content">
      <h3 class="modal-title">Change email</h3>
      <form id="editEmailForm" method="POST" action="">
        <input type="hidden" name="action" value="update_email">
        <div class="modal-form-group">
          <input type="email" id="editEmail" name="email" class="modal-input"
            value="<?= htmlspecialchars($customerEmail) ?>" required>
        </div>
        <div class="modal-actions">
          <button type="button" class="btn-cancel" onclick="closeModal('editEmailModal')">Cancel</button>
          <button type="submit" class="btn-save">Save</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Delete Account Modal -->
  <div id="deleteAccountModal" class="edit-modal">
    <div class="modal-overlay"></div>
    <div class="modal-content">
      <h3 class="modal-title">Delete account</h3>
      <p class="verify-message">Type <strong>delete</strong> to confirm account deletion.</p>
      <form id="deleteAccountForm" method="POST" action="">
        <div class="modal-form-group">
          <input type="text" id="deleteConfirmInput" name="confirm_text" class="modal-input" placeholder="delete"
            required>
        </div>
        <div class="modal-actions">
          <button type="button" class="btn-cancel" onclick="closeModal('deleteAccountModal')">Cancel</button>
          <button type="submit" class="btn-save" id="deleteAccountSubmit" disabled>Delete</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Country Modal -->
  <div id="editCountryModal" class="edit-modal hidden">
    <div class="modal-overlay"></div>
    <div class="modal-content">
      <h3 class="modal-title">Country</h3>
      <form id="editCountryForm" method="POST" action="">
        <input type="hidden" name="action" value="update_country">
        <div class="modal-form-group">
          <label for="editCountry" class="modal-label">Country</label>
          <input type="text" id="editCountry" name="country" class="modal-input"
            value="<?= htmlspecialchars($country) ?>" required disabled>
        </div>
        <div class="modal-actions">
          <button type="button" class="btn-cancel" onclick="closeModal('editCountryModal')">Cancel</button>
          <button type="submit" class="btn-save" disabled>Save</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Profile Picture Modal -->
  <div id="editProfilePictureModal" class="edit-modal hidden">
    <div class="modal-overlay"></div>
    <div class="modal-content">
      <h3 class="modal-title">Profile picture</h3>
      <form id="editProfilePictureForm" method="POST" action="" enctype="multipart/form-data">
        <input type="hidden" name="action" value="update_profile_picture">
        <div class="modal-form-group">
          <label for="editProfilePicture" class="modal-label">Upload new picture</label>
          <input type="file" id="editProfilePicture" name="profile_picture" class="modal-input-file" accept="image/*"
            required>
          <div class="file-info">Maximum file size: 2MB. Accepted formats: JPG, PNG, GIF</div>
        </div>
        <div class="modal-actions">
          <button type="button" class="btn-cancel" onclick="closeModal('editProfilePictureModal')">Cancel</button>
          <button type="submit" class="btn-save">Save</button>
        </div>
      </form>
    </div>
  </div>

  <script src="/PROGNET/assets/admin/js/sidebarAdmin.js"></script>
  <script src="/PROGNET/assets/customer/js/custom-alert.js"></script>
  <script src="/PROGNET/assets/customer/js/account-information.js"></script>
</body>

</html>