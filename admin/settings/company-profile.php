<?php
require_once __DIR__ . '/../init.php';

$isOwner = isset($_SESSION['role']) && $_SESSION['role'] === 'owner';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    if ($_POST['action'] === 'update_customer_service') {
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');

        $stmt = $conn->prepare("
            UPDATE company_profile
            SET customer_service_phone = ?, customer_service_email = ?
            WHERE id = 1
        ");
        $stmt->bind_param("ss", $phone, $email);
        $stmt->execute();
        $stmt->close();

        echo json_encode(['success' => true]);
        exit;
    }

    if ($_POST['action'] === 'update_managing_director') {
        $name = trim($_POST['name'] ?? '');

        $stmt = $conn->prepare("
            UPDATE company_profile
            SET managing_director = ?
            WHERE id = 1
        ");
        $stmt->bind_param("s", $name);
        $stmt->execute();
        $stmt->close();

        echo json_encode(['success' => true]);
        exit;
    }

    if ($_POST['action'] === 'update_company_content') {
        $type = $_POST['type'] ?? '';
        $content = trim($_POST['content'] ?? '');

        $allowed = [
            'about'  => 'about_uroam',
            'policy' => 'policy_uroam',
            'terms'  => 'terms_uroam'
        ];

        if (!isset($allowed[$type])) {
            echo json_encode(['success' => false]);
            exit;
        }

        $field = $allowed[$type];

        $stmt = $conn->prepare("
            UPDATE company_profile
            SET $field = ?
            WHERE id = 1
        ");
        $stmt->bind_param("s", $content);
        $stmt->execute();
        $stmt->close();

        echo json_encode(['success' => true]);
        exit;
    }

    echo json_encode(['success' => false]);
    exit;
}

$res = $conn->query("SELECT COUNT(*) AS total FROM admin");
$totalEmployees = $res->fetch_assoc()['total'];

$stmt = $conn->prepare("SELECT * FROM company_profile WHERE id = 1 LIMIT 1");
$stmt->execute();
$company = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$company) {
    $conn->query("
        INSERT IN TO company_profile 
        (customer_service_phone, customer_service_email, managing_director)
        VALUES ('', '', '')
    ");
    $company = [
        'customer_service_phone' => '',
        'customer_service_email' => '',
        'managing_director' => ''
    ];
}
?>


<!DOCTYPE html>
<html lang="id">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Company Profile — uRoam</title>

    <link rel="icon" type="image/uroam-icon" href="/PROGNET/images/uroam-title.ico">

    <!-- GANTI DI SINI (font jika perlu) -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Solway:wght@300;400;500;700;800&display=swap"
      rel="stylesheet"
    />

        <link rel="stylesheet" href="/PROGNET/assets/shared/css/style.css">
  <link rel="stylesheet" href="/PROGNET/assets/admin/css/sidebarAdmin.css">
    <link rel="stylesheet" href="/PROGNET/assets/admin/css/navbarAdmin.css">
    <link rel="stylesheet" href="/PROGNET/assets/admin/css/company-profile.css" />
  </head>

  <body class="cp">
<?php require_once __DIR__ . '/../_partials/sidebarAdmin.html'; ?>
<?php require_once __DIR__ . '/../_partials/navbarAdmin.html'; ?>

    <div id="content-wrapper">
      <main class="cp-main">
        <div class="cp-wrap">
          <!-- ========== Company information ========== -->
          <section class="card">
            <header class="card__head">
              <h1 class="card__title">Company detail</h1>
            </header>
  
            <!-- Pakai definition list agar semantik label–nilai -->
            <dl class="rows">
              <div class="row">
                <dt>Legal company name</dt>
                <dd>PT uRoam</dd>
              </div>

              <div class="row">
                <dt>NIB (Business Registration Number)</dt>
                <dd>123456789012345</dd>
              </div>

              <div class="row">
                <dt>TIN (Tax Identification Number)</dt>
                <dd>123456789012345</dd>
              </div>
              
              <div class="row">
                <dt>Total Employees</dt>
                <dd><?= $totalEmployees ?></dd>
              </div>
  
              <div class="row edit-row">
                <dt>Customer Service</dt>
                <dd class="edit-inline">
                  <div class="value-text">
                    <link href=" <">
                    <?= htmlspecialchars($company['customer_service_phone']) ?><br>
                    <?= htmlspecialchars($company['customer_service_email']) ?>
                  </div>

                  <?php if ($isOwner): ?>
                    <button class="edit-link" id="editCustomerService">
                      <span>Edit</span>
                      <img src="/PROGNET/images/icons/edit.svg" alt="edit">
                    </button>
                  <?php endif; ?>
                </dd>
              </div>
  
              <div class="row edit-row">
                <dt>Managing directors</dt>
                <dd class="edit-inline">
                    <div class="value-text">
                      <?= htmlspecialchars($company['managing_director']) ?>
                    </div>

                  <?php if ($isOwner): ?>
                    <button class="edit-link" id="editDirector">
                      <span>Edit</span>
                      <img src="/PROGNET/images/icons/edit.svg" alt="edit">
                    </button>
                  <?php endif; ?>
                </dd>
              </div>
            </dl>
          </section>
  
          <section class="card">
            <header class="card__head">
              <h2 class="card__title">About URoam</h2>
            </header>

            <form class="editable-content" data-type="about">
              <textarea class="cp-textarea" disabled><?= htmlspecialchars($company['about_uroam'] ?? '') ?></textarea>

              <div class="form-actions">
                  <?php if ($isOwner): ?>
                    <button type="button" class="btn-primary edit-btn">Edit</button>
                  <?php endif; ?>
                <button type="submit" class="btn-primary hidden save-btn">Save changes</button>
                <button type="button" class="btn-secondary hidden cancel-btn">Cancel</button>
              </div>
            </form>
          </section>

          <section class="card">
            <header class="card__head">
              <h2 class="card__title">Policy URoam</h2>
            </header>

            <form class="editable-content" data-type="policy">
              <textarea class="cp-textarea" disabled><?= htmlspecialchars($company['policy_uroam'] ?? '') ?></textarea>

              <div class="form-actions">
                  <?php if ($isOwner): ?>
                    <button type="button" class="btn-primary edit-btn">Edit</button>
                  <?php endif; ?>
                <button type="submit" class="btn-primary hidden save-btn">Save changes</button>
                <button type="button" class="btn-secondary hidden cancel-btn">Cancel</button>
              </div>
            </form>
          </section>

          <section class="card">
            <header class="card__head">
              <h2 class="card__title">Terms URoam</h2>
            </header>

            <form class="editable-content" data-type="terms">
              <textarea class="cp-textarea" disabled><?= htmlspecialchars($company['terms_uroam'] ?? '') ?></textarea>

              <div class="form-actions">
                  <?php if ($isOwner): ?>
                    <button type="button" class="btn-primary edit-btn">Edit</button>
                  <?php endif; ?>
                <button type="submit" class="btn-primary hidden save-btn">Save changes</button>
                <button type="button" class="btn-secondary hidden cancel-btn">Cancel</button>
              </div>
            </form>
          </section>

          <div id="customerServiceModal" class="modal-overlay hidden">
            <div class="modal">

              <div class="modal-header">
                <h2>Edit Customer Service</h2>
                <button class="close-btn" id="closeCustomerService">✕</button>
              </div>

              <form id="customerServiceForm">

                <div class="form-group">
                  <label>Phone number</label>
                  <input 
                    type="text" 
                    name="phone" 
                    value="<?= htmlspecialchars($company['customer_service_phone']) ?>"
                    required
                  >
                </div>

                <div class="form-group">
                  <label>Email address</label>
                  <input 
                    type="email" 
                    name="email" 
                    value="<?= htmlspecialchars($company['customer_service_email']) ?>"
                    required
                  >
                </div>

                <div class="modal-footer">
                  <button type="button" class="btn-secondary" id="cancelCustomerService">
                    Cancel
                  </button>
                  <button type="submit" class="btn-primary">
                    Save changes
                  </button>
                </div>
              </form>
            </div>
          </div>

          <div id="directorModal" class="modal-overlay hidden">
            <div class="modal">

              <div class="modal-header">
                <h2>Edit Managing Director</h2>
                <button class="close-btn" id="closeDirectorModal">✕</button>
              </div>

              <form id="directorForm">

                <div class="form-group">
                  <label>Managing director name</label>
                  <input
                    type="text"
                    name="name"
                    value="<?= htmlspecialchars($company['managing_director']) ?>"
                    required
                  >
                </div>

                <div class="modal-footer">
                  <button type="button" class="btn-secondary" id="cancelDirectorModal">
                    Cancel
                  </button>
                  <button type="submit" class="btn-primary">
                    Save changes
                  </button>
                </div>

              </form>
            </div>
          </div>
        </div>
  
      </main>
    </div>
    <script src="/PROGNET/assets/admin/js/sidebarAdmin.js"></script>
    <script src="/PROGNET/assets/admin/js/navbarAdmin.js"></script>
<script>
const csModal = document.getElementById('customerServiceModal');
const openBtn = document.getElementById('editCustomerService');
const closeBtn = document.getElementById('closeCustomerService');
const cancelBtn = document.getElementById('cancelCustomerService');
const csForm = document.getElementById('customerServiceForm');

openBtn.onclick = () => {
  csModal.classList.remove('hidden');
};

closeBtn.onclick = cancelBtn.onclick = () => {
  csModal.classList.add('hidden');
};

csForm.onsubmit = (e) => {
  e.preventDefault();

  const formData = new URLSearchParams({
    action: 'update_customer_service',
    phone: csForm.phone.value,
    email: csForm.email.value
  });

  fetch('', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: formData
  })
  .then(res => res.json())
  .then(res => {
    if (res.success) {
      // update UI tanpa reload
      document.querySelector('.row.edit-row .value-text').innerHTML =
        csForm.phone.value + '<br>' + csForm.email.value;

      csModal.classList.add('hidden');
    }
  });
};
</script>
<script>
const directorModal = document.getElementById('directorModal');
const openDirectorBtn = document.getElementById('editDirector');
const closeDirectorBtn = document.getElementById('closeDirectorModal');
const cancelDirectorBtn = document.getElementById('cancelDirectorModal');
const directorForm = document.getElementById('directorForm');

openDirectorBtn.onclick = () => {
  directorModal.classList.remove('hidden');
};

closeDirectorBtn.onclick = cancelDirectorBtn.onclick = () => {
  directorModal.classList.add('hidden');
};

directorForm.onsubmit = (e) => {
  e.preventDefault();

  const formData = new URLSearchParams({
    action: 'update_managing_director',
    name: directorForm.name.value
  });

  fetch('', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: formData
  })
  .then(res => res.json())
  .then(res => {
    if (res.success) {

      // update UI TANPA reload
      openDirectorBtn
        .closest('.edit-row')
        .querySelector('.value-text')
        .textContent = directorForm.name.value;

      directorModal.classList.add('hidden');
    }
  });
};
</script>
<script>
document.querySelectorAll('.editable-content').forEach(form => {
  const textarea = form.querySelector('textarea');
  const editBtn = form.querySelector('.edit-btn');
  const saveBtn = form.querySelector('[type="submit"]');
  const cancelBtn = form.querySelector('.cancel-btn');
  const type = form.dataset.type;

  let originalValue = textarea.value;

  editBtn.onclick = () => {
    originalValue = textarea.value;
    textarea.disabled = false;

    editBtn.classList.add('hidden');
    saveBtn.classList.remove('hidden');
    cancelBtn.classList.remove('hidden');
  };

  cancelBtn.onclick = () => {
    textarea.value = originalValue;
    textarea.disabled = true;

    editBtn.classList.remove('hidden');
    saveBtn.classList.add('hidden');
    cancelBtn.classList.add('hidden');
  };

  form.onsubmit = (e) => {
    e.preventDefault();

    fetch('', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({
        action: 'update_company_content',
        type: type,
        content: textarea.value
      })
    })
    .then(res => res.json())
    .then(res => {
      if (res.success) {
        textarea.disabled = true;

        editBtn.classList.remove('hidden');
        saveBtn.classList.add('hidden');
        cancelBtn.classList.add('hidden');
      }
    });
  };
});
</script>

  </body>
</html>
