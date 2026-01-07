// Security page interactions

(function () {
  const toggles = document.querySelectorAll('.toggle-password');
  toggles.forEach((btn) => {
    btn.addEventListener('click', () => {
      const targetId = btn.getAttribute('data-target');
      const input = document.getElementById(targetId);
      if (!input) return;
      const isPassword = input.type === 'password';
      input.type = isPassword ? 'text' : 'password';
    });
  });

  const form = document.getElementById('changePasswordForm');
  if (!form) return;

  const currentInput = document.getElementById('currentPassword');
  const newInput = document.getElementById('newPassword');
  const confirmInput = document.getElementById('confirmPassword');
  const currentError = document.getElementById('current-error');
  const confirmError = document.getElementById('confirm-error');
  const pwdHint = document.getElementById('pwd-hint');
  const criteriaItems = {
    length: document.querySelector('[data-criteria="length"]'),
    upper: document.querySelector('[data-criteria="upper"]'),
    lower: document.querySelector('[data-criteria="lower"]'),
    symbol: document.querySelector('[data-criteria="symbol"]'),
  };

  const setCurrentError = (message) => {
    if (!currentError) return;
    currentError.textContent = message || 'Wrong password';
    currentError.hidden = !message;
  };

  const validateCriteria = (value) => {
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
    return checks.length && checks.upper && checks.lower && checks.symbol;
  };

  const validateConfirm = () => {
    if (!confirmInput || !newInput || !confirmError) return true;
    if (!confirmInput.value) {
      confirmError.hidden = true;
      return true;
    }
    const match = confirmInput.value === newInput.value;
    confirmError.hidden = match;
    if (!match) confirmError.textContent = 'Passwords do not match';
    return match;
  };

  if (newInput) {
    newInput.addEventListener('input', (e) => {
      const value = e.target.value || '';
      if (pwdHint) pwdHint.hidden = value.length === 0;
      validateCriteria(value);
      validateConfirm();
    });
    newInput.addEventListener('focus', () => {
      if (pwdHint && newInput.value.length > 0) pwdHint.hidden = false;
    });
    newInput.addEventListener('blur', () => {
      if (pwdHint && newInput.value.length === 0) pwdHint.hidden = true;
    });
  }

  if (confirmInput) {
    confirmInput.addEventListener('input', validateConfirm);
  }

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    setCurrentError('');
    if (confirmError) confirmError.hidden = true;

    const currentPassword = currentInput ? currentInput.value : '';
    const newPassword = newInput ? newInput.value : '';
    const confirmPassword = confirmInput ? confirmInput.value : '';

    if (!currentPassword || !newPassword || !confirmPassword) {
      alert.error('Please fill in all fields.');
      return;
    }

    if (!validateConfirm()) {
      return;
    }

    const strongEnough = validateCriteria(newPassword);
    if (!strongEnough) {
      alert.error('Password must be at least 8 characters and include uppercase, lowercase, and symbol.');
      return;
    }

    if (currentPassword === newPassword) {
      alert.error('New password must be different from current password.');
      return;
    }

    const payload = new URLSearchParams();
    payload.append('action', 'change_password');
    payload.append('current_password', currentPassword);
    payload.append('new_password', newPassword);
    payload.append('confirm_password', confirmPassword);

    try {
      const response = await fetch('/PROGNET/customer/api/update-profile.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: payload.toString(),
      });

      const data = await response.json();
      if (data.success) {
        alert.success(data.message || 'Password updated successfully');
        form.reset();
        if (pwdHint) pwdHint.hidden = true;
        validateCriteria('');
      } else {
        if (data.message && data.message.toLowerCase().includes('wrong password')) {
          setCurrentError('Wrong password');
        } else {
          alert.error(data.message || 'Failed to update password');
        }
      }
    } catch (err) {
      alert.error('Network error. Please try again.');
    }
  });
})();
