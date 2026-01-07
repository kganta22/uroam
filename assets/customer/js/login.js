// Login page functionality
document.addEventListener('DOMContentLoaded', () => {
  const togglePassword = document.querySelector('.toggle-password');
  const passwordInput = document.getElementById('password');
  const eyeIcon = document.querySelector('.eye-icon');

  if (togglePassword && passwordInput) {
    togglePassword.addEventListener('click', () => {
      const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
      passwordInput.setAttribute('type', type);

      // Toggle eye icon (you can change the icon source if you have different icons)
      eyeIcon.style.opacity = type === 'text' ? '1' : '0.5';
    });
  }

  // Form validation
  const loginForm = document.querySelector('.login-form');
  if (loginForm) {
    loginForm.addEventListener('submit', (e) => {
      const email = document.getElementById('email').value;
      const password = document.getElementById('password').value;

      if (!email || !password) {
        e.preventDefault();
        alert('Please fill in all fields');
        return;
      }

      // Form will submit normally to PHP backend
    });
  }
});
