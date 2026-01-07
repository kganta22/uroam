// Account Information Page JavaScript

// Toggle edit profile picture
function toggleEditProfilePicture() {
  const profilePictureView = document.getElementById('profilePictureView');
  const profilePictureEdit = document.getElementById('profilePictureEdit');
  const uploadArea = document.getElementById('uploadArea');
  const fileInput = document.getElementById('editProfilePicture');
  const editBtn = document.getElementById('editProfilePictureBtn');

  // Toggle display
  const isEditing = profilePictureEdit.style.display === 'block';

  if (isEditing) {
    // Cancel edit mode
    profilePictureView.style.display = 'flex';
    profilePictureEdit.style.display = 'none';
    editBtn.textContent = 'Edit';

    // Reset file input
    fileInput.value = '';

    // Reset upload area
    uploadArea.innerHTML = `<svg class="upload-icon" viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="2">
      <rect x="8" y="24" width="48" height="32" rx="2"></rect>
      <circle cx="28" cy="32" r="4"></circle>
      <path d="M56 24V16a2 2 0 0 0-2-2H10a2 2 0 0 0-2 2v8"></path>
      <path d="M32 16v16"></path>
      <path d="M28 12v8"></path>
      <path d="M36 12v8"></path>
    </svg>
    <p class="upload-text">Upload image</p>`;
  } else {
    // Enter edit mode
    profilePictureView.style.display = 'none';
    profilePictureEdit.style.display = 'block';
    editBtn.textContent = 'Edit';

    // Make upload area clickable to trigger file input
    uploadArea.addEventListener('click', () => fileInput.click());

    // Handle file selection
    fileInput.addEventListener('change', (e) => {
      const file = e.target.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = (event) => {
          uploadArea.innerHTML = `<img src="${event.target.result}" style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; margin: 0 auto;">`;
        };
        reader.readAsDataURL(file);
      }
    });
  }
}

// Open modal
function openModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
  }
}

// Close modal
function closeModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    modal.classList.remove('active');
    document.body.style.overflow = '';
  }
}

// Close modal when clicking overlay
document.addEventListener('DOMContentLoaded', () => {
  const modals = document.querySelectorAll('.edit-modal');

  modals.forEach(modal => {
    const overlay = modal.querySelector('.modal-overlay');
    if (overlay) {
      overlay.addEventListener('click', () => {
        modal.classList.remove('active');
        document.body.style.overflow = '';
      });
    }
  });

  // Handle form submissions
  const forms = document.querySelectorAll('.edit-modal form');
  forms.forEach(form => {
    if (form.id === 'deleteAccountForm') return; // handled separately

    form.addEventListener('submit', async (e) => {
      e.preventDefault();

      const formData = new FormData(form);
      const submitBtn = form.querySelector('.btn-save');
      const originalText = submitBtn.textContent;

      submitBtn.disabled = true;
      submitBtn.textContent = 'Saving...';

      try {
        const response = await fetch('/PROGNET/customer/api/update-profile.php', {
          method: 'POST',
          body: formData
        });

        const result = await response.json();

        if (result.success) {
          // Reload page to show updated data
          alert.success(result.message || 'Data updated successfully!');
          setTimeout(() => {
            window.location.reload();
          }, 1500);
        } else {
          alert.error(result.message || 'Failed to update');
          submitBtn.disabled = false;
          submitBtn.textContent = originalText;
        }
      } catch (error) {
        console.error('Error:', error);
        alert.error('An error occurred. Please try again.');
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
      }
    });
  });

  // Handle profile picture form (inline edit)
  const profilePictureForm = document.getElementById('editProfilePictureForm');
  if (profilePictureForm && !profilePictureForm.closest('.edit-modal')) {
    profilePictureForm.addEventListener('submit', async (e) => {
      e.preventDefault();

      const formData = new FormData(profilePictureForm);
      const submitBtn = profilePictureForm.querySelector('.btn-save');
      const originalText = submitBtn.textContent;

      submitBtn.disabled = true;
      submitBtn.textContent = 'Saving...';

      try {
        const response = await fetch('/PROGNET/customer/api/update-profile.php', {
          method: 'POST',
          body: formData
        });

        const result = await response.json();

        if (result.success) {
          // Reload page to show updated data
          alert.success(result.message || 'Profile picture updated successfully!');
          setTimeout(() => {
            window.location.reload();
          }, 1500);
        } else {
          alert.error(result.message || 'Failed to update');
          submitBtn.disabled = false;
          submitBtn.textContent = originalText;
        }
      } catch (error) {
        console.error('Error:', error);
        alert.error('An error occurred. Please try again.');
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
      }
    });
  }

  // Delete account modal interactions
  const deleteBtn = document.getElementById('deleteAccountBtn');
  const deleteModal = document.getElementById('deleteAccountModal');
  const deleteForm = document.getElementById('deleteAccountForm');
  const deleteInput = document.getElementById('deleteConfirmInput');
  const deleteSubmit = document.getElementById('deleteAccountSubmit');

  if (deleteBtn && deleteModal && deleteForm && deleteInput && deleteSubmit) {
    deleteBtn.addEventListener('click', () => openModal('deleteAccountModal'));

    const updateDeleteButtonState = () => {
      const value = deleteInput.value.trim().toLowerCase();
      deleteSubmit.disabled = value !== 'delete';
    };

    deleteInput.addEventListener('input', updateDeleteButtonState);
    updateDeleteButtonState();

    deleteForm.addEventListener('submit', (e) => {
      e.preventDefault();
      const value = deleteInput.value.trim().toLowerCase();
      if (value !== 'delete') {
        alert.error('Please type "delete" to confirm.');
        return;
      }

      deleteSubmit.disabled = true;
      deleteSubmit.textContent = 'Deleting...';

      const formData = new FormData();
      formData.append('action', 'delete_account');
      formData.append('confirm_text', value);

      fetch('/PROGNET/customer/api/update-profile.php', {
        method: 'POST',
        body: formData
      })
        .then(res => res.json())
        .then(result => {
          if (result.success) {
            alert.success(result.message || 'Account deleted');
            setTimeout(() => {
              window.location.href = '/PROGNET/customer/auth/login.php';
            }, 1200);
          } else {
            alert.error(result.message || 'Failed to delete account');
            deleteSubmit.disabled = false;
            deleteSubmit.textContent = 'Delete';
          }
        })
        .catch(() => {
          alert.error('An error occurred. Please try again.');
          deleteSubmit.disabled = false;
          deleteSubmit.textContent = 'Delete';
        });
    });
  }
});

// Email verification
let verificationCodeTimer = null;
let verificationCodeCountdown = 0;

// Auto-send verification code when modal opens
const verifyEmailModal = document.getElementById('verifyEmailModal');
if (verifyEmailModal) {
  const originalOpenModal = window.openModal;
  window.openModal = function (modalId) {
    if (modalId === 'verifyEmailModal') {
      sendVerificationCode();
    }
    originalOpenModal.call(this, modalId);
  };

  // Handle verify email form submission
  const verifyEmailForm = document.getElementById('verifyEmailForm');
  if (verifyEmailForm) {
    verifyEmailForm.addEventListener('submit', async (e) => {
      e.preventDefault();

      const verificationCode = document.getElementById('verificationCode').value;
      const submitBtn = verifyEmailForm.querySelector('.btn-save');
      const originalText = submitBtn.textContent;

      submitBtn.disabled = true;
      submitBtn.textContent = 'Verifying...';

      try {
        const formData = new FormData();
        formData.append('action', 'verify_email');
        formData.append('verification_code', verificationCode);

        const response = await fetch('/PROGNET/customer/api/update-profile.php', {
          method: 'POST',
          body: formData
        });

        const result = await response.json();

        if (result.success) {
          alert.success(result.message || 'Email verified successfully!');
          setTimeout(() => {
            closeModal('verifyEmailModal');
            window.location.reload();
          }, 1500);
        } else {
          alert.error(result.message || 'Verification failed');
          submitBtn.disabled = false;
          submitBtn.textContent = originalText;
        }
      } catch (error) {
        console.error('Error:', error);
        alert.error('An error occurred. Please try again.');
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
      }
    });
  }
}

// Send verification code
async function sendVerificationCode() {
  try {
    const formData = new FormData();
    formData.append('action', 'resend_verification_code');

    const response = await fetch('/PROGNET/customer/api/update-profile.php', {
      method: 'POST',
      body: formData
    });

    const result = await response.json();

    if (result.success) {
      // Start countdown timer
      startResendCodeTimer();
    }
  } catch (error) {
    console.error('Error sending verification code:', error);
  }
}

// Resend verification code
async function resendVerificationCode() {
  const resendBtn = document.getElementById('resendCodeBtn');

  if (resendBtn.disabled) return;

  try {
    const formData = new FormData();
    formData.append('action', 'resend_verification_code');

    const response = await fetch('/PROGNET/customer/api/update-profile.php', {
      method: 'POST',
      body: formData
    });

    const result = await response.json();

    if (result.success) {
      alert.success('Verification code sent!');
      startResendCodeTimer();
    } else {
      alert.error(result.message || 'Failed to resend code');
    }
  } catch (error) {
    console.error('Error:', error);
    alert.error('An error occurred. Please try again.');
  }
}

// Start resend code timer
function startResendCodeTimer() {
  const resendBtn = document.getElementById('resendCodeBtn');
  verificationCodeCountdown = 60;
  resendBtn.disabled = true;

  if (verificationCodeTimer) {
    clearInterval(verificationCodeTimer);
  }

  const updateTimer = () => {
    resendBtn.textContent = `Resend code (${verificationCodeCountdown}s)`;
    verificationCodeCountdown--;

    if (verificationCodeCountdown < 0) {
      clearInterval(verificationCodeTimer);
      resendBtn.textContent = 'Resend code';
      resendBtn.disabled = false;
    }
  };

  updateTimer(); // Call immediately
  verificationCodeTimer = setInterval(updateTimer, 1000);
}

// Close modal with Escape key
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') {
    const activeModal = document.querySelector('.edit-modal.active');
    if (activeModal) {
      activeModal.classList.remove('active');
      document.body.style.overflow = '';

      // Clear timer when closing verify email modal
      if (activeModal.id === 'verifyEmailModal' && verificationCodeTimer) {
        clearInterval(verificationCodeTimer);
      }
    }
  }
});

// Clear timer when closing verify email modal
const closeModalOriginal = window.closeModal;
window.closeModal = function (modalId) {
  if (modalId === 'verifyEmailModal' && verificationCodeTimer) {
    clearInterval(verificationCodeTimer);
  }
  closeModalOriginal.call(this, modalId);
};

