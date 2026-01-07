<?php
require_once __DIR__ . '/../_guards/customerGuard.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Log in and security - uRoam</title>

	<link rel="icon" type="image/uroam-icon" href="/PROGNET/images/uroam-title.ico">

	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Solway:wght@400;600;700&display=swap" rel="stylesheet">

	<link rel="stylesheet" href="/PROGNET/assets/shared/css/style.css">
	<link rel="stylesheet" href="/PROGNET/assets/admin/css/sidebarAdmin.css">
	<link rel="stylesheet" href="/PROGNET/assets/customer/css/navbar.css">
	<link rel="stylesheet" href="/PROGNET/assets/customer/css/footer.css">
	<link rel="stylesheet" href="/PROGNET/assets/customer/css/security.css">
	<link rel="stylesheet" href="/PROGNET/assets/customer/css/custom-alert.css">
</head>

<body>
	<!-- Sidebar -->
	<?php require_once __DIR__ . '/../_partials/sidebarCustomer.html'; ?>

	<!-- Navbar -->
	<?php require_once __DIR__ . '/../_partials/navbar.php'; ?>

	<div class="content-wrapper">
		<div class="security-wrapper">
			<div class="security-card">
				<div class="security-header">
					<button class="back-btn" type="button" onclick="window.history.back()">
						<img src="/PROGNET/images/icons/arrow-left.svg" alt="Back">
					</button>
					<h2 class="security-title">Security</h2>
					<span class="header-spacer"></span>
				</div>

				<div class="security-section">
					<h4 class="section-title">Password</h4>

					<form id="changePasswordForm" class="security-form" autocomplete="off">
						<div class="form-group">
							<label for="currentPassword">Current password</label>
							<div class="input-with-icon">
								<input type="password" id="currentPassword" name="current_password"
									placeholder="Password" required>
								<button type="button" class="toggle-password" data-target="currentPassword">
									<img src="/PROGNET/images/icons/eye.svg" alt="Toggle password">
								</button>
							</div>
							<div class="error-message" id="current-error" hidden>Wrong password</div>
						</div>

						<div class="form-group">
							<label for="newPassword">New password</label>
							<div class="input-with-icon">
								<input type="password" id="newPassword" name="new_password" placeholder="Password"
									required>
								<button type="button" class="toggle-password" data-target="newPassword">
									<img src="/PROGNET/images/icons/eye.svg" alt="Toggle password">
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
							<label for="confirmPassword">Confirm new password</label>
							<div class="input-with-icon">
								<input type="password" id="confirmPassword" name="confirm_password"
									placeholder="Confirm password" required>
								<button type="button" class="toggle-password" data-target="confirmPassword">
									<img src="/PROGNET/images/icons/eye.svg" alt="Toggle password">
								</button>
							</div>
							<div class="error-message" id="confirm-error" hidden>Passwords do not match</div>
						</div>

						<div class="form-footer">
							<a class="forgot-link" href="/PROGNET/customer/auth/forgot-password.php">Forgot password</a>
							<button type="submit" class="btn-primary">Change password</button>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>

	<?php require_once __DIR__ . '/../_partials/footer.php'; ?>

	<script src="/PROGNET/assets/admin/js/sidebarAdmin.js"></script>
	<script src="/PROGNET/assets/customer/js/custom-alert.js"></script>
	<script src="/PROGNET/assets/customer/js/security.js"></script>
</body>

</html>