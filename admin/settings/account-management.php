<?php
require_once __DIR__ . '/../init.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_id'], $_POST['new_password'])) {
    $adminId = (int) $_POST['admin_id'];
    $newPassword = password_hash($_POST['new_password'], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("
        UPDATE admin 
        SET password = ?
        WHERE id = ?
    ");
    $stmt->bind_param("si", $newPassword, $adminId);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }

    $stmt->close();
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {

    header('Content-Type: application/json');

    $adminId = (int) $_POST['delete_id'];

    // Ambil data admin
    $check = $conn->prepare("
        SELECT profile_picture, role 
        FROM admin 
        WHERE id = ?
    ");
    $check->bind_param("i", $adminId);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Admin not found'
        ]);
        exit;
    }

    $admin = $result->fetch_assoc();

    // Proteksi owner
    if ($admin['role'] === 'owner') {
        echo json_encode([
            'success' => false,
            'message' => 'Owner account cannot be deleted'
        ]);
        exit;
    }

    $profilePicture = $admin['profile_picture'];
    $check->close();

    // Delete admin
    $delete = $conn->prepare("
        DELETE FROM admin 
        WHERE id = ?
    ");
    $delete->bind_param("i", $adminId);

    if ($delete->execute()) {

        // Hapus file foto (jika ada)
        if (!empty($profilePicture)) {
            $filePath = $_SERVER['DOCUMENT_ROOT'] . $profilePicture;
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        echo json_encode([
            'success' => true
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to delete admin'
        ]);
    }

    $delete->close();
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'], $_POST['password'])) {

    //AMBIL & SIAPKAN DATA
    $email      = trim($_POST['email']);
    $password   = $_POST['password'];

    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name'] ?? '');
    $full_name  = trim($first_name . ' ' . $last_name);
    $full_name  = $full_name !== '' ? $full_name : null;

    $job   = $_POST['job'] ?? null;
    $phone = $_POST['phone'] ?? null;

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    //CEK EMAIL DUPLIKAT
    $check = $conn->prepare("SELECT id FROM admin WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        echo "<script>alert('Email already exists');</script>";
        $check->close();
        exit;
    }
    $check->close();

    // HANDLE UPLOAD PROFILE PICTURE
    $profile_picture = null;

    if (
        isset($_FILES['profile_picture']) &&
        $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK
    ) {
        $allowedExt = ['jpg', 'jpeg', 'png'];
        $fileTmp  = $_FILES['profile_picture']['tmp_name'];
        $fileName = $_FILES['profile_picture']['name'];
        $fileSize = $_FILES['profile_picture']['size'];

        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        // Validasi ekstensi
        if (!in_array($ext, $allowedExt)) {
            echo "<script>alert('Only JPG and PNG files are allowed');</script>";
            exit;
        }

        // Validasi ukuran (4MB)
        if ($fileSize > 4 * 1024 * 1024) {
            echo "<script>alert('Image must be under 4MB');</script>";
            exit;
        }

        // Nama file unik
        $newFileName = uniqid('admin_', true) . '.' . $ext;

        // Path server & DB
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/PROGNET/database/uploads/admin/';
        $uploadPath = $uploadDir . $newFileName;

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        if (!move_uploaded_file($fileTmp, $uploadPath)) {
            echo "<script>alert('Failed to upload image');</script>";
            exit;
        }

        $profile_picture = '/PROGNET/database/uploads/admin/' . $newFileName;
    }

    // INSERT KE DATABASE
    $stmt = $conn->prepare("
        INSERT INTO admin 
        (email, password, first_name, last_name, full_name, job, phone, profile_picture)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "ssssssss",
        $email,
        $hashedPassword,
        $first_name,
        $last_name,
        $full_name,
        $job,
        $phone,
        $profile_picture
    );

    if ($stmt->execute()) {
        header("Location: /PROGNET/admin/settings/account-management.php");
        exit;
    } else {
        echo "<script>alert('Failed to add admin');</script>";
    }

    $stmt->close();
}


$stmt = $conn->prepare("
SELECT id, email, first_name, last_name, full_name, job, phone, role
FROM admin
  ORDER BY id ASC
");
$stmt->execute();
$admins = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Account management</title>

      <link rel="icon" type="image/uroam-icon" href="/PROGNET/images/uroam-title.ico">

      <link href="https://fonts.googleapis.com/css2?family=Solway:wght@300;400;500;700;800&display=swap" rel="stylesheet"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="/PROGNET/assets/shared/css/style.css">
  <link rel="stylesheet" href="/PROGNET/assets/admin/css/sidebarAdmin.css">
    <link rel="stylesheet" href="/PROGNET/assets/admin/css/navbarAdmin.css">
  <link rel="stylesheet" href="/PROGNET/assets/admin/css/account-management.css" />
</head>
<body>
<?php require_once __DIR__ . '/../_partials/sidebarAdmin.html'; ?>
<?php require_once __DIR__ . '/../_partials/navbarAdmin.html'; ?>

    <div id="content-wrapper">

      <div class="container">
        <div class="page-header">
          <h1>Account management</h1>

          <?php if ($_SESSION['role'] === 'owner'): ?>
          <button id="openModalBtn" class="add-btn">
            <span>＋</span> Add a new admin profile
          </button>
          <?php endif; ?>
        </div>

        <!-- TABLE -->
        <div class="table-wrapper">
          <table>
            <thead>
              <tr>
                <th>Email</th>
                <th>Name</th>
                <th>Role</th>
                <th></th>
              </tr>
            </thead>
              <tbody>
              <?php while ($row = $admins->fetch_assoc()): ?>
                <tr class="admin-row <?= $row['role'] === 'owner' ? 'owner-row' : ''; ?>">
                  <td class="email-cell">
                    <button 
                      class="dropdown-btn" 
                      data-target="detail-<?= $row['id']; ?>">
                      ▸
                    </button>
                    <?= htmlspecialchars($row['email']); ?>
                  </td>

                  <td>
                    <?= htmlspecialchars($row['full_name'] ?? '-'); ?>
                  </td>

                  <td>
                    <div class="role">
                      <span><?= ucfirst($row['role']); ?></span>
                    </div>
                  </td>

                  <td class="actions">
                    <?php if ($_SESSION['role'] === 'owner' && $row['role'] !== 'owner'): ?>
                    <button class="delete-admin-btn" data-id="<?= $row['id']; ?>">
                      <img src="/PROGNET/images/icons/trash.svg" width="30">
                    </button>
                    <?php endif; ?>
                  </td>
                </tr>

                <!-- DETAIL -->
                <tr id="detail-<?= $row['id']; ?>" class="admin-detail hidden">
                  <td colspan="4">
                    <div class="detail-box">
                      <p><strong>First name:</strong> <?= $row['first_name'] ?: '-'; ?></p>
                      <p><strong>Last name:</strong> <?= $row['last_name'] ?: '-'; ?></p>
                      <p><strong>Job title:</strong> <?= $row['job'] ?: '-'; ?></p>
                      <p><strong>Phone:</strong> <?= $row['phone'] ?: '-'; ?></p>
                      <?php if ($_SESSION['role'] === 'owner'): ?>
                      <p class="password-row">
                        <button type="button" class="change-password-btn" data-id="<?= $row['id']; ?>">Change Password</button>
                      </p>
                      <?php endif; ?>
                    </div>
                  </td>
                </tr>
              <?php endwhile; ?>
              </tbody>
          </table>
        </div>
      
      </div>
      
      <!-- MODAL -->
      <div id="modalOverlay" class="modal-overlay hidden">
      
        <div class="modal">
      
          <div class="modal-header">
            <h2>Add a new admin profile</h2>
            <button id="closeModalBtn" class="close-btn">✕</button>
          </div>
      
          <div class="modal-body">
            <form id="addAdminForm" method="POST" action="/PROGNET/admin/settings/account-management.php" enctype="multipart/form-data">
                <div class="form-group">
                  <label>Email</label>
                  <input type="email" name="email" required>
                </div>

                <div class="form-group">
                  <label>Password</label>
                  <input type="password" name="password" required>
                </div>
          
                <h3 class="section-title">Personal Information</h3>
          
                <div class="grid-2">
                  <div class="form-group">
                    <label>First name (optional)</label>
                    <input type="text" name="first_name">
                  </div>
                  <div class="form-group">
                    <label>Last name (optional)</label>
                    <input type="text" name="last_name">
                  </div>
                  <div class="form-group">
                    <label>Job title (optional)</label>
                    <select name="job">
                      <option value="Operations">Operations</option>
                      <option value="Guide">Guide</option>
                      <option value="Finance">Finance</option>
                    </select>
                  </div>
                  <div class="form-group">
                    <label>Phone number (optional)</label>
                    <div class="phone-input">
                      <input type="text" name="phone">
                    </div>
                  </div>
                </div>
          
                <div class="form-group">
                  <label>Profile picture</label>
                  <div class="upload-box">
                    <input type="file" name="profile_picture" id="profilePictureInput" accept=".jpg,.jpeg,.png" hidden>
                    <button type="button" class="upload-btn">Upload picture ⬆</button>

                      <div class="image-preview" style="display:none;">
                        <img id="profilePreview" alt="Preview" />
                      </div>
                      <p class="upload-hint">
                        Choose a clear profile picture so that customers can easily recognize you.<br>
                        <span>*.png, *.jpg files up to 4MB at least 180px x 180px</span>
                      </p>
                  </div>
                </div>

                <div class="modal-footer">
                  <button type="button" id="cancelModalBtn" class="btn-secondary">Cancel</button>
                  <button type="submit" class="btn-primary">Add admin</button>
                </div>
            </form>
          </div>
      
      
        </div>
      </div>

      <div id="changePasswordOverlay" class="modal-overlay hidden">
  <div class="modal">
    <div class="modal-header">
      <h2>Change Password</h2>
      <button class="close-btn" id="closeChangePassword">✕</button>
    </div>

    <div class="modal-body">
      <form id="changePasswordForm">
        <input type="hidden" name="admin_id" id="changePasswordAdminId">

        <div class="form-group">
          <label>New password</label>
          <input type="password" name="new_password" required>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn-secondary" id="cancelChangePassword">
            Cancel
          </button>
          <button type="submit" class="btn-primary">
            Update password
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
    </div>

    <script src="/PROGNET/assets/admin/js/sidebarAdmin.js"></script>
    <script src="/PROGNET/assets/admin/js/navbarAdmin.js"></script>
<script src="/PROGNET/assets/admin/js/account-management.js"></script>
<script>
  document.querySelectorAll('.dropdown-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    const targetId = btn.dataset.target;
    const detailRow = document.getElementById(targetId);

    const isHidden = detailRow.classList.contains('hidden');

    // Toggle
    detailRow.classList.toggle('hidden');
    btn.classList.toggle('active');

    // Optional: ganti icon
    btn.textContent = isHidden ? '▾' : '▸';
  });
});
</script>
<script>
  document.querySelectorAll('.delete-admin-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    const adminId = btn.dataset.id;

    if (!confirm('Delete this admin?')) return;

    fetch('/PROGNET/admin/settings/account-management.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `delete_id=${adminId}`
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        location.reload();
      } else {
        alert(data.message);
      }
    });
  });
});
</script> 
<script>
  const uploadBtn = document.querySelector('.upload-btn');
  const fileInput = document.getElementById('profilePictureInput');
  const previewBox = document.querySelector('.image-preview');
  const previewImg = document.getElementById('profilePreview');
  const hintText = document.querySelector('.upload-hint');


  // Buka file picker
  uploadBtn.addEventListener('click', () => {
    fileInput.click();
  });

  // Preview saat file dipilih
  fileInput.addEventListener('change', () => {
    const file = fileInput.files[0];

    if (!file) return;

    // Validasi tipe file di frontend (opsional tapi bagus)
    if (!file.type.startsWith('image/')) {
      alert('Please select an image file');
      fileInput.value = '';
      previewBox.style.display = 'none';
      return;
    }

    const reader = new FileReader();

    reader.onload = (e) => {
      previewImg.src = e.target.result;
      previewBox.style.display = 'block';
      hintText.style.display = 'none';
    };

    reader.readAsDataURL(file);
  });
</script> 
<script>
  const changeOverlay = document.getElementById('changePasswordOverlay');
  const adminIdInput = document.getElementById('changePasswordAdminId');

  document.querySelectorAll('.change-password-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      adminIdInput.value = btn.dataset.id;
      changeOverlay.classList.remove('hidden');
    });
  });

  document.getElementById('closeChangePassword').onclick = () => {
    changeOverlay.classList.add('hidden');
  };

  document.getElementById('cancelChangePassword').onclick = () => {
    changeOverlay.classList.add('hidden');
  };
</script>
<script>
  document.getElementById('changePasswordForm').addEventListener('submit', e => {
    e.preventDefault();

    const formData = new URLSearchParams(
      new FormData(e.target)
    );

    fetch('/PROGNET/admin/settings/account-management.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: formData.toString()
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        alert('Password updated');
        location.reload();
      } else {
        alert('Failed to update password');
      }
    });
  });
</script>
</body>
</html>
