<?php
require_once __DIR__ . '/init.php';

// SEARCH FILTER
$search = "";
$param = "";

if (!empty($_GET['q'])) {
  $search = "WHERE p.title LIKE ?";
  $param = "%" . $_GET['q'] . "%";
}

// QUERY PRODUK (SEARCH)
$query = "
    SELECT 
        p.id,
        p.title,
        p.reference_code,
        p.thumbnail,
        p.is_active,
        (SELECT COUNT(*) FROM bookings WHERE product_id = p.id) as has_bookings
    FROM products p
    $search
    ORDER BY p.id DESC
";

$stmt = $conn->prepare($query);

if (!empty($search)) {
  $stmt->bind_param("s", $param);
}

$stmt->execute();
$products = $stmt->get_result();

// DELETE PRODUCT
if (isset($_POST['delete_id'])) {
  $product_id = intval($_POST['delete_id']);

  // Check if product has bookings (finance records)
  $chk = $conn->prepare("SELECT COUNT(*) AS total FROM bookings WHERE product_id = ?");
  $chk->bind_param("i", $product_id);
  $chk->execute();
  $count = $chk->get_result()->fetch_assoc()['total'];
  $chk->close();

  if ($count > 0) {
    header("Location: home.php?error=has_booking");
    exit;
  }

  // Delete order_request first
  $stmt = $conn->prepare("DELETE FROM order_request WHERE product_id = ?");
  $stmt->bind_param("i", $product_id);
  $stmt->execute();
  $stmt->close();

  // Delete photos from storage
  $photos = $conn->prepare("SELECT photo_path FROM product_photos WHERE product_id = ?");
  $photos->bind_param("i", $product_id);
  $photos->execute();
  $result = $photos->get_result();
  while ($p = $result->fetch_assoc()) {
    if (file_exists($p['photo_path']))
      unlink($p['photo_path']);
  }
  $photos->close();

  // Delete itinerary file
  $it = $conn->prepare("SELECT itinerary_file FROM products WHERE id = ?");
  $it->bind_param("i", $product_id);
  $it->execute();
  $idata = $it->get_result()->fetch_assoc();
  $it->close();

  if (!empty($idata['itinerary_file']) && file_exists($idata['itinerary_file'])) {
    unlink($idata['itinerary_file']);
  }

  // Delete from DB
  $stmt = $conn->prepare("DELETE FROM product_photos WHERE product_id = ?");
  $stmt->bind_param("i", $product_id);
  $stmt->execute();
  $stmt->close();

  $stmt = $conn->prepare("DELETE FROM product_prices WHERE product_id = ?");
  $stmt->bind_param("i", $product_id);
  $stmt->execute();
  $stmt->close();

  $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
  $stmt->bind_param("i", $product_id);
  $stmt->execute();
  $stmt->close();

  header("Location: home.php?deleted=1");
  exit;
}

// INACTIVE PRODUCT
if (isset($_POST['inactive_id'])) {
  $product_id = intval($_POST['inactive_id']);
  $new_status = intval($_POST['new_status']);

  $stmt = $conn->prepare("UPDATE products SET is_active = ? WHERE id = ?");
  $stmt->bind_param("ii", $new_status, $product_id);
  $stmt->execute();
  $stmt->close();

  header("Location: home.php?status_updated=1");
  exit;
}

$countQuery = "SELECT COUNT(*) AS total FROM products p $search";
$stmtCount = $conn->prepare($countQuery);

if (!empty($search)) {
  $stmtCount->bind_param("s", $param);
}

$stmtCount->execute();
$countResult = $stmtCount->get_result()->fetch_assoc();
$totalProducts = $countResult['total'];

?>

<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Home — Supplier Portal</title>

  <!-- Google Font -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Solway:wght@300;400;500;700;800&display=swap" rel="stylesheet" />

  <link rel="icon" type="image/uroam-icon" href="/PROGNET/images/uroam-title.ico">

  <!-- CSS -->
  <link rel="stylesheet" href="/PROGNET/assets/shared/css/style.css" />
  <link rel="stylesheet" href="/PROGNET/assets/admin/css/sidebarAdmin.css" />
  <link rel="stylesheet" href="/PROGNET/assets/admin/css/navbarAdmin.css" />
  <link rel="stylesheet" href="/PROGNET/assets/admin/css/home.css" />
</head>

<body class="hm">
  <?php require_once __DIR__ . '/_partials/sidebarAdmin.html'; ?>
  <?php require_once __DIR__ . '/_partials/navbarAdmin.html'; ?>

  <div id="content-wrapper">
    <main class="hm-main">
      <!-- HERO AREA -->
      <section class="hm-hero">

        <div class="hm-toolbar">
          <div class="hm-filters">

            <!-- Filter by Product -->
            <label class="hm-filter">
              <span>Filter by Product</span>
              <div class="hm-input-icon">
                <form method="GET" class="hm-search-form">
                  <input type="search" name="q" placeholder="Search"
                    value="<?= isset($_GET['q']) ? htmlspecialchars($_GET['q']) : '' ?>" />
                  <img src="/PROGNET/images/icons/search.svg" alt="">
                </form>
              </div>
            </label>

            <div class="hm-result-hint">
              Showing <?= $totalProducts ?> products
            </div>
          </div>

          <!-- Create new product -->
          <a class="hm-create" href="/PROGNET/admin/products/create.php">
            <span style="font-size: 30px;">＋</span> Create new product
          </a>
        </div>

        <!-- Product List -->
        <section class="hm-list" aria-labelledby="tblTitle">
          <h2 id="tblTitle" class="sr-only">Product List</h2>
          <div class="hm-table">
            <div class="hm-thead">
              <div class="col-product">Product</div>
              <div class="col-ref">Reference code</span></div>
              <div class="col-action">Action</div>
            </div>

            <?php while ($row = $products->fetch_assoc()): ?>
              <article class="hm-row">

                <!-- Product column -->
                <div class="col-product">
                  <img class="thumb"
                    src="<?= !empty($row['thumbnail']) ? $row['thumbnail'] : '/PROGNET/images/no-photo.png' ?>"
                    alt="Thumbnail">

                  <div class="meta">
                    <a href="/PROGNET/admin/products/edit.php?id=<?= $row['id'] ?>" class="title">
                      <?= ($row['title']) ?>
                    </a>
                    <?php if ($row['is_active'] == 0): ?>
                      <span style="color: #999; font-size: 12px; margin-left: 8px;">(Inactive)</span>
                    <?php endif; ?>
                  </div>
                </div>

                <!-- Reference code -->
                <div class="col-ref">
                  <?= ($row['reference_code']) ?>
                </div>

                <!-- Action buttons -->
                <div class="col-action">
                  <a href="/PROGNET/admin/products/edit.php?id=<?= $row['id'] ?>"
                    class="btn btn--outline-primary">Edit</a>
                  
                  <?php if ($row['has_bookings'] > 0): ?>
                    <!-- Product has bookings - show inactive/active button -->
                    <?php if ($row['is_active'] == 1): ?>
                      <button type="button" class="btn btn--outline-warning"
                        onclick="openInactiveModal(<?= $row['id'] ?>, 0)">Inactive</button>
                    <?php else: ?>
                      <button type="button" class="btn btn--outline-success"
                        onclick="openInactiveModal(<?= $row['id'] ?>, 1)">Activate</button>
                    <?php endif; ?>
                  <?php else: ?>
                    <!-- Product has no bookings - show delete button -->
                    <button type="button" class="btn btn--outline-danger"
                      onclick="openDeleteModal(<?= $row['id'] ?>)">Delete</button>
                  <?php endif; ?>
                </div>

              </article>
            <?php endwhile; ?>

          </div>
        </section>
      </section>

    </main>
  </div>

  <!-- MODAL DELETE -->
  <div id="modal-delete" class="md">
    <a href="#" class="md__overlay" onclick="closeDeleteModal()"></a>

    <section class="md__card" role="dialog" aria-modal="true">
      <button type="button" class="md__close" onclick="closeModal()">✕</button>

      <h2 class="md__title">Delete Product</h2>
      <p class="md__text">
        Are you sure you want to delete this product?<br>
        This action cannot be undone.
      </p>

      <form method="POST">
        <input type="hidden" id="delete_id" name="delete_id">

        <div class="md__actions">
          <button type="button" class="md-btn md-btn--ghost" onclick="closeModal()">Cancel</button>
          <button type="submit" class="md-btn md-btn--danger">Delete</button>
        </div>
      </form>

    </section>
  </div>

  <!-- MODAL INACTIVE/ACTIVATE -->
  <div id="modal-inactive" class="md">
    <a href="#" class="md__overlay" onclick="closeModal()"></a>

    <section class="md__card" role="dialog" aria-modal="true">
      <button type="button" class="md__close" onclick="closeModal()">✕</button>

      <h2 class="md__title" id="inactive-title">Change Product Status</h2>
      <p class="md__text" id="inactive-text">
        Are you sure you want to change this product status?
      </p>

      <form method="POST">
        <input type="hidden" id="inactive_id" name="inactive_id">
        <input type="hidden" id="new_status" name="new_status">

        <div class="md__actions">
          <button type="button" class="md-btn md-btn--ghost" onclick="closeModal()">Cancel</button>
          <button type="submit" class="md-btn md-btn--primary" id="inactive-btn">Confirm</button>
        </div>
      </form>

    </section>
  </div>

  <script src="/PROGNET/assets/admin/js/sidebarAdmin.js"></script>
  <script src="/PROGNET/assets/admin/js/navbarAdmin.js"></script>
  <script>
    function openDeleteModal(id) {
      document.getElementById('delete_id').value = id;
      document.getElementById('modal-delete').classList.add('show');
    }

    function closeDeleteModal() {
      document.getElementById('modal-delete').classList.remove('show');
    }

    function openInactiveModal(id, newStatus) {
      document.getElementById('inactive_id').value = id;
      document.getElementById('new_status').value = newStatus;
      
      if (newStatus === 0) {
        document.getElementById('inactive-title').textContent = 'Inactive Product';
        document.getElementById('inactive-text').textContent = 'This product has booking records. Are you sure you want to make it inactive? It will no longer be visible to customers.';
        document.getElementById('inactive-btn').textContent = 'Inactive';
        document.getElementById('inactive-btn').className = 'md-btn md-btn--warning';
      } else {
        document.getElementById('inactive-title').textContent = 'Activate Product';
        document.getElementById('inactive-text').textContent = 'Are you sure you want to activate this product? It will be visible to customers again.';
        document.getElementById('inactive-btn').textContent = 'Activate';
        document.getElementById('inactive-btn').className = 'md-btn md-btn--success';
      }
      
      document.getElementById('modal-inactive').classList.add('show');
    }

    function closeModal() {
      document.querySelector('.md.show')?.classList.remove('show');
    }
  </script>

</body>

</html>