<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$isLoggedIn = isset($_SESSION['customer_id']);
$customerName = '';
$avatarPath = '/PROGNET/images/icons/no-profile.png';

if ($isLoggedIn) {
  $customerName = $_SESSION['customer_name'] ?? '';

  // Get customer profile picture
  if (isset($conn)) {
    $stmt = $conn->prepare("SELECT profile_picture FROM customers WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $_SESSION['customer_id']);
    $stmt->execute();
    $customer = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!empty($customer['profile_picture'])) {
      $avatarPath = $customer['profile_picture'];
    }
  }
}
?>

<header class="nav-customer">
  <div class="nav-customer__inner">

    <!-- Right Section: Language + Login/Profile -->
    <div class="nav-right">
      <!-- Language Selector -->
      <div class="nav-language">
        <button class="lang-btn" id="langToggle">
          <img src="/PROGNET/images/icons/english.png" alt="US" class="flag-icon">
          <img src="/PROGNET/images/icons/dropdown.svg" alt="" class="dropdown-icon">
        </button>
        <div class="lang-menu" id="langMenu">
          <button class="lang-item" data-lang="en">
            <img src="/PROGNET/images/icons/english.png" alt="English">
            <span>English</span>
          </button>
          <button class="lang-item" data-lang="id">
            <img src="/PROGNET/images/icons/indonesia.png" alt="Indonesia">
            <span>Indonesia</span>
          </button>
          <button class="lang-item" data-lang="es">
            <img src="/PROGNET/images/icons/spanish.png" alt="Spanish">
            <span>Spanish</span>
          </button>
        </div>
      </div>

      <?php if ($isLoggedIn): ?>
        <!-- Profile Dropdown (Logged In) -->
        <div class="nav-profile">
          <button class="profile-btn" id="profileToggle">
            <img src="<?= htmlspecialchars($avatarPath) ?>" alt="Avatar" class="avatar">
            <span class="name"><?= htmlspecialchars($customerName) ?></span>
            <img class="caret" src="/PROGNET/images/icons/dropdown.svg" alt="">
          </button>

          <div class="profile-menu" id="profileMenu">
            <a href="/PROGNET/customer/dashboard/account-information.php" class="pm-item">
              <img src="/PROGNET/images/icons/settings.svg" alt="">
              <span>Account Information</span>
            </a>

            <a href="/PROGNET/customer/dashboard/security.php" class="pm-item">
              <img src="/PROGNET/images/icons/lock.svg" alt="">
              <span>Security</span>
            </a>

            <a href="/PROGNET/customer/auth/logout.php" class="pm-item logout">
              <img src="/PROGNET/images/icons/logout.svg" alt="">
              <span>Logout</span>
            </a>
          </div>
        </div>
      <?php else: ?>
        <!-- Login Button (Not Logged In) -->
        <a href="/PROGNET/customer/auth/login.php" class="nav-login-btn">
          <img src="/PROGNET/images/icons/person.svg" alt="">
          <span>Log in</span>
        </a>
      <?php endif; ?>
    </div>

  </div>
</header>

<!-- Google Translate -->
<div id="google_translate_element" style="display: none;"></div>
<script type="text/javascript"
  src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>

<script>
  // Initialize Google Translate
  function googleTranslateElementInit() {
    new google.translate.TranslateElement({
      pageLanguage: 'en',
      includedLanguages: 'en,id,es',
      autoDisplay: false
    }, 'google_translate_element');
  }

  // Helper function to change language
  function changeGoogleLanguage(lang) {
    const selectField = document.querySelector('.goog-te-combo');
    if (selectField) {
      selectField.value = lang;
      selectField.dispatchEvent(new Event('change'));
    }
  }

  // Language toggle
  const langToggle = document.getElementById('langToggle');
  const langMenu = document.getElementById('langMenu');
  const flagIcon = langToggle?.querySelector('.flag-icon');

  // Load saved language from localStorage
  const savedLang = localStorage.getItem('selectedLanguage') || 'en';
  if (flagIcon) {
    if (savedLang === 'en') {
      flagIcon.src = '/PROGNET/images/icons/english.png';
    } else if (savedLang === 'id') {
      flagIcon.src = '/PROGNET/images/icons/indonesia.png';
    } else if (savedLang === 'es') {
      flagIcon.src = '/PROGNET/images/icons/spanish.png';
    }
  }

  // Apply saved translation on page load
  window.addEventListener('load', () => {
    if (savedLang === 'id') {
      setTimeout(() => {
        changeGoogleLanguage('id');
      }, 500);
    }
  });

  langToggle?.addEventListener('click', (e) => {
    e.stopPropagation();
    langMenu.classList.toggle('active');
    langToggle.classList.toggle('active');
  });

  // Handle language selection
  document.querySelectorAll('.lang-item').forEach(item => {
    item.addEventListener('click', (e) => {
      const lang = item.getAttribute('data-lang');
      let flagSrc = '/PROGNET/images/icons/english.png';

      if (lang === 'id') {
        flagSrc = '/PROGNET/images/icons/indonesia.png';
      } else if (lang === 'es') {
        flagSrc = '/PROGNET/images/icons/spanish.png';
      }

      if (flagIcon) {
        flagIcon.src = flagSrc;
      }

      // Save to localStorage
      localStorage.setItem('selectedLanguage', lang);

      // Trigger Google Translate
      setTimeout(() => {
        changeGoogleLanguage(lang);
      }, 100);

      // Close menu
      langMenu?.classList.remove('active');
      langToggle?.classList.remove('active');
    });
  });

  document.addEventListener('click', (e) => {
    if (!e.target.closest('.nav-language')) {
      langMenu?.classList.remove('active');
      langToggle?.classList.remove('active');
    }
  });

  // Profile toggle
  const profileToggle = document.getElementById('profileToggle');
  const profileMenu = document.getElementById('profileMenu');

  profileToggle?.addEventListener('click', (e) => {
    e.stopPropagation();
    profileMenu.classList.toggle('active');
    profileToggle.classList.toggle('active');
  });

  document.addEventListener('click', (e) => {
    if (!e.target.closest('.nav-profile')) {
      profileMenu?.classList.remove('active');
      profileToggle?.classList.remove('active');
    }
  });
</script>