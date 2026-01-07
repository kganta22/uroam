<?php
/* Customer Phone Verification Page */
require_once __DIR__ . '/../_guards/guestGuard.php';
require_once __DIR__ . '/../../database/connect.php';

$phoneDisplay = htmlspecialchars($_SESSION['signup_phone'] ?? ($_GET['phone'] ?? ''));
// Pastikan halaman ini hanya diakses setelah sign up; jika tidak, kembalikan ke sign up.
if (!$phoneDisplay) {
	header('Location: /PROGNET/customer/auth/signup.php');
	exit();
}
if ($phoneDisplay && substr($phoneDisplay, 0, 1) !== '+') {
	$phoneDisplay = '+' . $phoneDisplay;
}
$formError = null;
$successMessage = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$otp = $_POST['otp'] ?? '';

	// Validation
	if (!$otp) {
		$formError = 'Verification code is required';
	} elseif (!preg_match('/^\d{6}$/', $otp)) {
		$formError = 'Invalid verification code format';
	} else {
		// Get OTP from database
		$normalizedPhone = $_SESSION['signup_phone'] ?? null;

		if (!$normalizedPhone) {
			$formError = 'Invalid phone number';
		} else {
			$query = "SELECT * FROM otp_verifications WHERE target = ? AND type = 'phone' ORDER BY created_at DESC LIMIT 1";
			$stmt = $conn->prepare($query);
			$stmt->bind_param("s", $normalizedPhone);
			$stmt->execute();
			$result = $stmt->get_result();
			$otpRecord = $result->fetch_assoc();
			$stmt->close();

			if (!$otpRecord) {
				$formError = 'Verification code not found. Please request a new one.';
			} elseif (strtotime($otpRecord['expired_at']) < time()) {
				$formError = 'Verification code has expired. Please request a new one.';
			} elseif (!password_verify($otp, $otpRecord['otp_hash'])) {
				$formError = 'Invalid verification code. Please try again.';
			} else {
				// OTP verified - create customer account
				$email = $_SESSION['signup_email'] ?? '';
				$firstName = $_SESSION['signup_first_name'] ?? '';
				$lastName = $_SESSION['signup_last_name'] ?? '';
				$country = $_SESSION['signup_country'] ?? '';
				$passwordHash = $_SESSION['signup_password'] ?? '';
				$countryCode = $_SESSION['signup_country_code'] ?? '';

				if ($email && $firstName && $lastName && $country && $passwordHash) {
					$fullName = $firstName . ' ' . $lastName;
					$createdAt = date('Y-m-d H:i:s');
					$insertQuery = "INSERT INTO customers (full_name, first_name, last_name, email, password, phone, country, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
					$insertStmt = $conn->prepare($insertQuery);
					$insertStmt->bind_param("ssssssss", $fullName, $firstName, $lastName, $email, $passwordHash, $normalizedPhone, $country, $createdAt);

					if ($insertStmt->execute()) {
						$customerId = $insertStmt->insert_id;
						$insertStmt->close();

						// Mark OTP as verified and link to customer
						$updateQuery = "UPDATE otp_verifications SET verified_at = ?, customer_id = ? WHERE id = ?";
						$updateStmt = $conn->prepare($updateQuery);
						$updateStmt->bind_param("sii", $createdAt, $customerId, $otpRecord['id']);
						$updateStmt->execute();
						$updateStmt->close();

						// Clear session
						unset($_SESSION['signup_phone']);
						unset($_SESSION['signup_email']);
						unset($_SESSION['signup_country_code']);
						unset($_SESSION['signup_first_name']);
						unset($_SESSION['signup_last_name']);
						unset($_SESSION['signup_country']);
						unset($_SESSION['signup_password']);

						// Redirect ke halaman login setelah verifikasi berhasil
						header('Location: /PROGNET/customer/auth/login.php');
						exit();
					} else {
						$formError = 'Failed to create account. Please try again.';
						$insertStmt->close();
					}
				} else {
					$formError = 'Missing signup information. Please sign up again.';
				}
			}
		}
	}
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Phone Verification - uRoam</title>

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
					<div class="signup-header">
						<button type="button" class="back-button"
							onclick="window.location.href='/PROGNET/customer/auth/signup.php'" aria-label="Back">
							<img src="/PROGNET/images/icons/arrow-left.svg" alt="Back" width="24" height="24">
						</button>
						<h1 class="login-title">Enter verification code</h1>
					</div>

					<?php if ($formError): ?>
						<div class="error-message"
							style="margin-bottom: 20px; padding: 12px; background: #fee; border: 1px solid #fcc; border-radius: 8px; color: #c0392b;">
							<?= htmlspecialchars($formError) ?>
						</div>
					<?php endif; ?>

					<div class="verification-subtitle" style="text-align: center; margin-bottom: 24px;">
						<div class="app-icon" style="text-align: center; margin-bottom: 16px;">
							<img src="/PROGNET/images/icons/ic-whatsapp.png" alt="WhatsApp" width="60" height="60">
						</div>
						<p style="color: #666; font-size: 14px;">Enter the code we sent to
							<?= $phoneDisplay ? $phoneDisplay : 'your phone' ?>.</p>
					</div>

					<form class="verification-form" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>"
						method="POST">
						<div class="form-group">
							<label for="otp" class="form-label">Verification code</label>
							<input type="text" name="otp" id="otp" inputmode="numeric" pattern="\d{6}" maxlength="6"
								class="form-input" placeholder="000000" required>
						</div>
						<div class="resend-row" style="text-align: center; margin: 16px 0;">
							<button type="button" class="resend-link" id="resend-button" disabled
								style="background: none; border: none; color: #666; cursor: pointer; font-size: 14px; padding: 0;">Resend
								verification code in <span id="countdown">60</span> seconds</button>
						</div>
						<button type="submit" class="btn-create-account">Continue</button>
					</form>
				</div>
			</div>
		</main>
	</div>

	<?php require_once __DIR__ . '/../_partials/footer.php'; ?>

	<script src="/PROGNET/assets/admin/js/sidebarAdmin.js"></script>
	<script>
		document.addEventListener('DOMContentLoaded', () => {
			const resendButton = document.getElementById('resend-button');
			const countdownSpan = document.getElementById('countdown');
			const otpInput = document.getElementById('otp');
			const COUNTDOWN_KEY = 'otpCountdownStart';
			const DURATION = 60; // seconds
			let intervalId = null;

			function getRemaining() {
				const start = parseInt(localStorage.getItem(COUNTDOWN_KEY) || '0', 10);
				if (!start) return 0;
				const elapsed = Math.floor((Date.now() - start) / 1000);
				const remaining = DURATION - elapsed;
				return remaining > 0 ? remaining : 0;
			}

			function setCountdownStart() {
				localStorage.setItem(COUNTDOWN_KEY, Date.now().toString());
			}

			function clearCountdown() {
				localStorage.removeItem(COUNTDOWN_KEY);
			}

			function updateCountdown() {
				const remaining = getRemaining();
				if (remaining <= 0) {
					resendButton.disabled = false;
					resendButton.textContent = 'Resend verification code';
					countdownSpan.textContent = '0';
					return false;
				}
				resendButton.disabled = true;
				countdownSpan.textContent = remaining;
				resendButton.textContent = 'Resend verification code in ' + remaining + ' seconds';
				return true;
			}

			function startCountdown() {
				if (intervalId) clearInterval(intervalId);
				if (!updateCountdown()) return;
				intervalId = setInterval(() => {
					if (!updateCountdown()) {
						clearInterval(intervalId);
						intervalId = null;
					}
				}, 1000);
			}

			// Initial countdown based on stored start time; if none, set now
			if (!getRemaining()) {
				setCountdownStart();
			}
			startCountdown();

			// Resend button handler
			resendButton.addEventListener('click', async (e) => {
				e.preventDefault();
				if (resendButton.disabled) return;
				resendButton.disabled = true;
				resendButton.textContent = 'Sending...';
				try {
					const resp = await fetch('/PROGNET/customer/api/resend-otp.php', {
						method: 'POST',
						headers: { 'Accept': 'application/json' },
					});
					const data = await resp.json();
					if (!resp.ok || !data.success) {
						alert(data.message || 'Failed to resend verification code');
					} else {
						setCountdownStart();
						startCountdown();
					}
				} catch (err) {
					alert('Failed to resend verification code');
				} finally {
					if (!getRemaining()) {
						resendButton.disabled = false;
						resendButton.textContent = 'Resend verification code';
					}
				}
			});

			// Auto-format OTP input (6 digits only)
			if (otpInput) {
				otpInput.addEventListener('input', (e) => {
					e.target.value = e.target.value.replace(/[^0-9]/g, '').slice(0, 6);
				});
			}

			// Optional: clear countdown when leaving page after success (handled server-side by redirect)
			window.addEventListener('pageshow', () => {
				// Keep countdown across reloads; nothing to do here
			});
		});
	</script>
</body>

</html>