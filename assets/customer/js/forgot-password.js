// Forgot Password page functionality
document.addEventListener('DOMContentLoaded', () => {
  const formEmail = document.getElementById('form-email');
  const formVerify = document.getElementById('form-verify');

  const emailDisplay = document.getElementById('email-display');
  const backToEmailBtn = document.getElementById('back-to-email');

  const verifyError = document.getElementById('verify-error');
  const confirmError = document.getElementById('confirm-error');

  const pwdHint = document.getElementById('pwd-hint');
  const criteriaItems = {
    length: document.querySelector('[data-criteria="length"]'),
    upper: document.querySelector('[data-criteria="upper"]'),
    lower: document.querySelector('[data-criteria="lower"]'),
    symbol: document.querySelector('[data-criteria="symbol"]'),
  };

  // Toggle password visibility for new password
  const togglePassword = document.querySelector('.toggle-password');
  const newPasswordInput = document.getElementById('new-password');
  const eyeIcon = document.querySelector('.toggle-password .eye-icon');

  if (togglePassword && newPasswordInput) {
    togglePassword.addEventListener('click', () => {
      const type = newPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
      newPasswordInput.setAttribute('type', type);
      eyeIcon.style.opacity = type === 'text' ? '1' : '0.5';
    });
  }

  // Toggle password visibility for confirm password
  const togglePasswordConfirm = document.querySelector('.toggle-password-confirm');
  const confirmPasswordInput = document.getElementById('confirm-password');
  const eyeIconConfirm = document.querySelector('.toggle-password-confirm .eye-icon');

  if (togglePasswordConfirm && confirmPasswordInput) {
    togglePasswordConfirm.addEventListener('click', () => {
      const type = confirmPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
      confirmPasswordInput.setAttribute('type', type);
      eyeIconConfirm.style.opacity = type === 'text' ? '1' : '0.5';
    });
  }

  // Step 1 handled by normal POST; no JS submission needed.

  // Step 2: Verification & password reset
  if (formVerify) {
    const confirmPasswordInput = document.getElementById('confirm-password');

    const setVerifyError = (message) => {
      if (!verifyError) return;
      verifyError.textContent = message || '';
      verifyError.hidden = !message;
    };

    const updateCriteria = (value) => {
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
    };

    const validateConfirm = () => {
      if (!confirmPasswordInput || !newPasswordInput) return true;
      if (!confirmPasswordInput.value) {
        if (confirmError) confirmError.hidden = true;
        return true;
      }
      const match = confirmPasswordInput.value === newPasswordInput.value;
      if (confirmError) confirmError.hidden = match;
      if (!match && confirmError) confirmError.textContent = 'Passwords do not match';
      return match;
    };

    if (newPasswordInput) {
      newPasswordInput.addEventListener('input', (e) => {
        const value = e.target.value || '';
        if (pwdHint) pwdHint.hidden = value.length === 0;
        updateCriteria(value);
        validateConfirm();
      });
      newPasswordInput.addEventListener('focus', () => {
        if (pwdHint && newPasswordInput.value.length > 0) pwdHint.hidden = false;
      });
      newPasswordInput.addEventListener('blur', () => {
        if (pwdHint && newPasswordInput.value.length === 0) pwdHint.hidden = true;
      });
    }

    if (confirmPasswordInput) {
      confirmPasswordInput.addEventListener('input', validateConfirm);
    }

    formVerify.addEventListener('submit', (e) => {
      const code = document.getElementById('code').value;
      const newPassword = newPasswordInput ? newPasswordInput.value : '';
      const confirmPassword = confirmPasswordInput ? confirmPasswordInput.value : '';

      setVerifyError('');

      if (!code || !newPassword || !confirmPassword) {
        e.preventDefault();
        setVerifyError('Please fill in all fields.');
        return;
      }

      if (!validateConfirm()) {
        e.preventDefault();
        return;
      }

      const strongEnough = newPassword.length >= 8 && /[A-Z]/.test(newPassword) && /[a-z]/.test(newPassword) && /[^A-Za-z0-9]/.test(newPassword);
      if (!strongEnough) {
        e.preventDefault();
        setVerifyError('Password must be at least 8 characters and include uppercase, lowercase, and symbol.');
        return;
      }

      // submit to backend; no alert on client
    });
  }
});
