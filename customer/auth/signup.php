<?php
/* Customer Sign Up Page */
require_once __DIR__ . '/../_guards/guestGuard.php';
require_once __DIR__ . '/../../database/connect.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__, 2));
$dotenv->load();


$phoneValue = htmlspecialchars($_SESSION['signup_phone'] ?? '');
$emailValue = htmlspecialchars($_SESSION['signup_email'] ?? '');
$formError = null;
$phoneError = null;
$emailError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$countryCode = trim($_POST['country_code'] ?? '');
	$phone = trim($_POST['phone'] ?? '');
	$country = trim($_POST['country'] ?? '');
	$firstName = trim($_POST['first_name'] ?? '');
	$lastName = trim($_POST['last_name'] ?? '');
	$email = trim($_POST['email'] ?? '');
	$password = $_POST['password'] ?? '';
	$confirmPassword = $_POST['confirm_password'] ?? '';
	$agree = isset($_POST['agree']) ? true : false;

	// Persist latest input values
	$phoneValue = htmlspecialchars($phone);
	$emailValue = htmlspecialchars($email);

	// Debug: Uncomment untuk melihat data yang dikirim
	error_log("DEBUG POST DATA: " . json_encode($_POST));
	error_log("DEBUG FIELDS: countryCode='$countryCode', phone='$phone', country='$country', firstName='$firstName', lastName='$lastName', email='$email', password='$password', confirmPassword='$confirmPassword', agree='$agree'");

	// Basic validation dengan detailed error message
	$missingFields = [];
	if (!$countryCode)
		$missingFields[] = 'Country code';
	if (!$phone)
		$missingFields[] = 'Phone number';
	if (!$country)
		$missingFields[] = 'Country';
	if (!$firstName)
		$missingFields[] = 'First name';
	if (!$lastName)
		$missingFields[] = 'Last name';
	if (!$email)
		$missingFields[] = 'Email';
	if (!$password)
		$missingFields[] = 'Password';
	if (!$confirmPassword)
		$missingFields[] = 'Confirm password';

	if (!empty($missingFields)) {
		$formError = 'Missing fields: ' . implode(', ', $missingFields);
	} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
		$formError = 'Invalid email format';
	} elseif ($password !== $confirmPassword) {
		$formError = 'Passwords do not match';
	} elseif (strlen($password) < 8) {
		$formError = 'Password must be at least 8 characters';
	} elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[^A-Za-z0-9]/', $password)) {
		$formError = 'Password must contain uppercase, lowercase, and symbol';
	} elseif (!$agree) {
		$formError = 'You must agree to terms and conditions';
	} else {
		// Generate OTP (6 digits)
		$otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

		// Normalize phone number
		$phoneDigits = preg_replace('/\D+/', '', $phone);
		if (strlen($phoneDigits) > 0 && $phoneDigits[0] === '0') {
			$phoneDigits = substr($phoneDigits, 1);
		}
		// Remove + from country code and combine
		$countryCodeDigits = str_replace('+', '', $countryCode);
		$normalizedPhone = $countryCodeDigits . $phoneDigits;

		// Check duplicates for phone / email
		$dupQuery = "SELECT email, phone FROM customers WHERE email = ? OR phone = ? LIMIT 1";
		$dupStmt = $conn->prepare($dupQuery);
		$dupStmt->bind_param("ss", $email, $normalizedPhone);
		$dupStmt->execute();
		$dupResult = $dupStmt->get_result();
		if ($dupRow = $dupResult->fetch_assoc()) {
			if (isset($dupRow['email']) && strcasecmp($dupRow['email'], $email) === 0) {
				$emailError = 'Email ' . htmlspecialchars($email) . ' is already used';
			}
			if (isset($dupRow['phone']) && $dupRow['phone'] === $normalizedPhone) {
				$phoneError = 'Phone number ' . htmlspecialchars($phone) . ' is already used';
			}
		}
		$dupStmt->close();

		if ($emailError || $phoneError) {
			// Skip OTP flow if duplicate
		} else {

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
			curl_close($curl);

			// Store signup data in session
			$_SESSION['signup_phone'] = $normalizedPhone;
			$_SESSION['signup_email'] = $email;
			$_SESSION['signup_country_code'] = $countryCode;
			$_SESSION['signup_first_name'] = $firstName;
			$_SESSION['signup_last_name'] = $lastName;
			$_SESSION['signup_country'] = $country;
			$_SESSION['signup_password'] = password_hash($password, PASSWORD_BCRYPT);

			// Redirect to phone verification
			header('Location: /PROGNET/customer/auth/phone-verification.php');
			exit();
		}
	}
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Sign Up - uRoam</title>

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
				<div class="login-card signup-card">
					<div class="signup-header">
						<button type="button" class="back-button" onclick="window.history.back()">
							<img src="/PROGNET/images/icons/arrow-left.svg" alt="Back"
								style="width: 24px; height: 24px;">
						</button>
						<h1 class="login-title">Sign Up</h1>
					</div>

					<?php if ($formError): ?>
						<div class="error-message"
							style="margin-bottom: 20px; padding: 12px; background: #fee; border: 1px solid #fcc; border-radius: 8px; color: #c0392b;">
							<?= htmlspecialchars($formError) ?>
						</div>
					<?php endif; ?>

					<form class="signup-form" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" method="POST">
						<div class="form-group">
							<label for="country_code" class="form-label">Country code</label>
							<div class="select-wrapper">
								<select id="country_code" name="country_code" class="form-input select-input" required>
									<option value="+1">+1 United States / Canada</option>
									<option value="+44">+44 United Kingdom</option>
									<option value="+61">+61 Australia</option>
									<option value="+62" selected>+62 Indonesia</option>
									<option value="+63">+63 Philippines</option>
									<option value="+65">+65 Singapore</option>
									<option value="+81">+81 Japan</option>
									<option value="+82">+82 South Korea</option>
									<option value="+84">+84 Vietnam</option>
									<option value="+86">+86 China</option>
									<option value="+91">+91 India</option>
									<option value="+92">+92 Pakistan</option>
									<option value="+94">+94 Sri Lanka</option>
									<option value="+95">+95 Myanmar</option>
									<option value="+60">+60 Malaysia</option>
									<option value="+66">+66 Thailand</option>
									<option value="+64">+64 New Zealand</option>
									<option value="+49">+49 Germany</option>
									<option value="+33">+33 France</option>
									<option value="+39">+39 Italy</option>
									<option value="+34">+34 Spain</option>
									<option value="+41">+41 Switzerland</option>
									<option value="+7">+7 Russia</option>
									<option value="+90">+90 Turkey</option>
									<option value="+971">+971 United Arab Emirates</option>
									<option value="+974">+974 Qatar</option>
									<option value="+966">+966 Saudi Arabia</option>
									<option value="+212">+212 Morocco</option>
									<option value="+27">+27 South Africa</option>
									<option value="+234">+234 Nigeria</option>
								</select>
								<img src="/PROGNET/images/icons/dropdown.svg" alt="Dropdown" class="dropdown-icon">
							</div>
						</div>

						<div class="form-group">
							<label for="phone" class="form-label">Phone number</label>
							<input type="text" id="phone" name="phone" class="form-input" placeholder="Phone number"
								value="<?= $phoneValue ?>" required>
							<?php if ($phoneError): ?>
								<div class="error-message"
									style="margin-top: 8px; padding: 10px; background: #fee; border: 1px solid #fcc; border-radius: 8px; color: #c0392b;">
									<?= $phoneError ?>
								</div>
							<?php endif; ?>
						</div>

						<div class="form-group">
							<label for="email" class="form-label">Email</label>
							<input type="email" id="email" name="email" class="form-input" placeholder="Email address"
								value="<?= $emailValue ?>" required>
							<?php if ($emailError): ?>
								<div class="error-message"
									style="margin-top: 8px; padding: 10px; background: #fee; border: 1px solid #fcc; border-radius: 8px; color: #c0392b;">
									<?= $emailError ?>
								</div>
							<?php endif; ?>
						</div>

						<div class="form-group">
							<label for="country" class="form-label">Country</label>
							<div class="select-wrapper">
								<select id="country" name="country" class="form-input select-input" required>
									<option value="Indonesia" selected>Indonesia</option>
									<option value="United States">United States</option>
									<option value="United Kingdom">United Kingdom</option>
									<option value="Australia">Australia</option>
									<option value="Singapore">Singapore</option>
									<option value="Malaysia">Malaysia</option>
									<option value="Philippines">Philippines</option>
									<option value="Thailand">Thailand</option>
									<option value="Vietnam">Vietnam</option>
									<option value="Japan">Japan</option>
									<option value="South Korea">South Korea</option>
									<option value="China">China</option>
									<option value="India">India</option>
									<option value="Germany">Germany</option>
									<option value="France">France</option>
									<option value="Netherlands">Netherlands</option>
									<option value="Spain">Spain</option>
									<option value="Italy">Italy</option>
									<option value="Canada">Canada</option>
									<option value="Mexico">Mexico</option>
									<option value="Brazil">Brazil</option>
									<option value="South Africa">South Africa</option>
									<option value="Turkey">Turkey</option>
									<option value="United Arab Emirates">United Arab Emirates</option>
									<option value="Saudi Arabia">Saudi Arabia</option>
									<option value="Qatar">Qatar</option>
									<option value="Bahrain">Bahrain</option>
									<option value="Switzerland">Switzerland</option>
									<option value="Sweden">Sweden</option>
									<option value="Norway">Norway</option>
									<option value="Denmark">Denmark</option>
									<option value="Poland">Poland</option>
									<option value="Czech Republic">Czech Republic</option>
									<option value="Portugal">Portugal</option>
									<option value="Ireland">Ireland</option>
									<option value="New Zealand">New Zealand</option>
								</select>
								<img src="/PROGNET/images/icons/dropdown.svg" alt="Dropdown" class="dropdown-icon">
							</div>
						</div>

						<div class="form-group">
							<label for="first_name" class="form-label">First Name</label>
							<input type="text" id="first_name" name="first_name" class="form-input"
								placeholder="First name" required>
						</div>

						<div class="form-group">
							<label for="last_name" class="form-label">Last Name</label>
							<input type="text" id="last_name" name="last_name" class="form-input"
								placeholder="Last name" required>
						</div>

						<div class="form-group">
							<label for="password" class="form-label">Your password <span
									class="required">*</span></label>
							<div class="password-wrapper">
								<input type="password" id="password" name="password" class="form-input"
									placeholder="Password" required>
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

						<div class="form-group confirm-group" id="confirm-group" hidden>
							<label for="confirm_password" class="form-label">Confirm your password <span
									class="required">*</span></label>
							<div class="password-wrapper">
								<input type="password" id="confirm_password" name="confirm_password" class="form-input"
									placeholder="Confirm password" required>
								<button type="button" class="toggle-password" aria-label="Toggle password visibility">
									<img src="/PROGNET/images/icons/eye.svg" alt="Show password" class="eye-icon">
								</button>
							</div>
							<div class="error-message" id="confirm-error" hidden
								style="margin-top: 8px; padding: 10px; background: #fee; border: 1px solid #fcc; border-radius: 8px; color: #c0392b;">
								Passwords do not match</div>
						</div>

						<label class="checkbox-row">
							<input type="checkbox" name="agree" required>
							<span class="checkbox-custom">
								<svg class="checkmark-icon" viewBox="0 0 24 24" width="16" height="16" fill="none"
									stroke="currentColor" stroke-width="3">
									<polyline points="20 6 9 17 4 12"></polyline>
								</svg>
							</span>
							<span class="checkbox-text">By signing up, I agree to the <a
									href="/PROGNET/customer/terms.php" class="terms-link">Terms &amp; conditions</a> and
								the <a href="/PROGNET/customer/policy.php" class="terms-link">privacy policy</a> of
								Panorama JTB.</span>
						</label>

						<button type="submit" class="btn-create-account">Create account</button>
					</form>
				</div>
			</div>
		</main>
	</div>

	<?php require_once __DIR__ . '/../_partials/footer.php'; ?>

	<script>
		document.addEventListener('DOMContentLoaded', () => {
			const toggles = document.querySelectorAll('.password-wrapper .toggle-password');
			toggles.forEach((toggle) => {
				const input = toggle.previousElementSibling;
				const eye = toggle.querySelector('.eye-icon');
				toggle.addEventListener('click', () => {
					const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
					input.setAttribute('type', type);
					eye.style.opacity = type === 'text' ? '1' : '0.5';
				});
			});

			const passwordInput = document.getElementById('password');
			const confirmGroup = document.getElementById('confirm-group');
			const confirmInput = document.getElementById('confirm_password');
			const confirmError = document.getElementById('confirm-error');
			const pwdHint = document.getElementById('pwd-hint');
			const criteriaItems = {
				length: document.querySelector('[data-criteria="length"]'),
				upper: document.querySelector('[data-criteria="upper"]'),
				lower: document.querySelector('[data-criteria="lower"]'),
				symbol: document.querySelector('[data-criteria="symbol"]'),
			};

			function updateCriteria(value) {
				const checks = {
					length: value.length >= 8,
					upper: /[A-Z]/.test(value),
					lower: /[a-z]/.test(value),
					symbol: /[^A-Za-z0-9]/.test(value),
				};

				Object.keys(checks).forEach((key) => {
					const el = criteriaItems[key];
					if (!el) return;
					el.classList.toggle('criteria-pass', checks[key]);
					el.classList.toggle('criteria-fail', !checks[key]);
				});
			}

			function validateConfirm() {
				if (!confirmInput) return;
				if (!confirmInput.value) {
					confirmError.hidden = true;
					confirmInput.setCustomValidity('');
					return;
				}
				const match = confirmInput.value === passwordInput.value;
				confirmError.hidden = match;
				confirmInput.setCustomValidity(match ? '' : 'Passwords do not match');
			}

			function handlePasswordInput() {
				const value = passwordInput.value;
				const hasText = value.length > 0;
				pwdHint.hidden = !hasText;
				confirmGroup.hidden = !hasText;
				updateCriteria(value);
				if (confirmInput && confirmInput.value) validateConfirm();
			}

			if (passwordInput) {
				passwordInput.addEventListener('input', handlePasswordInput);
				passwordInput.addEventListener('focus', () => {
					if (passwordInput.value.length > 0) pwdHint.hidden = false;
				});
				passwordInput.addEventListener('blur', () => {
					if (!passwordInput.value.length) pwdHint.hidden = true;
				});
			}

			if (confirmInput) {
				confirmInput.addEventListener('input', validateConfirm);
			}

			// Handle checkbox untuk enable/disable button
			const agreeCheckbox = document.querySelector('input[name="agree"]');
			const submitBtn = document.querySelector('.btn-create-account');

			function updateButtonState() {
				if (submitBtn) {
					submitBtn.disabled = !agreeCheckbox.checked;
				}
			}

			if (agreeCheckbox) {
				agreeCheckbox.addEventListener('change', updateButtonState);
				updateButtonState(); // Set initial state
			}
		});
	</script>
	<script src="/PROGNET/assets/admin/js/sidebarAdmin.js"></script>
</body>

</html>