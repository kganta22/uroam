<?php
require_once __DIR__ . '/../init.php';

// ====== AMBIL ID ======
$product_id = (int) ($_GET['id'] ?? 0);

// ====== PRODUCT ======
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    die("Product not found");
}

// ====== PRICES ======
$prices = [
    'private' => ['adult_price' => 0, 'child_price' => 0],
    'group' => ['adult_price' => 0, 'child_price' => 0]
];

$p = $conn->prepare("SELECT category, adult_price, child_price FROM product_prices WHERE product_id = ?");
$p->bind_param("i", $product_id);
$p->execute();
$r = $p->get_result();
while ($row = $r->fetch_assoc()) {
    $prices[$row['category']] = $row;
}
$p->close();

// ====== PHOTOS ======
$photos = [];
$ph = $conn->prepare("SELECT photo_path FROM product_photos WHERE product_id = ?");
$ph->bind_param("i", $product_id);
$ph->execute();
$photos = $ph->get_result()->fetch_all(MYSQLI_ASSOC);
$ph->close();

// ====== CATEGORIES ======
$categories = [];
$res = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");
while ($row = $res->fetch_assoc()) {
    $categories[] = $row;
}

// category yg dimiliki product
$selectedCats = [];
$rc = $conn->prepare("SELECT category_id FROM product_categories WHERE product_id = ?");
$rc->bind_param("i", $product_id);
$rc->execute();
$catRes = $rc->get_result();
while ($c = $catRes->fetch_assoc()) {
    $selectedCats[] = $c['category_id'];
}
$rc->close();


// =================================================
// ================= UPDATE =========================
// =================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title = $_POST['title'];
    $full_desc = $_POST['full_description'];
    $include = $_POST['include'];
    $exclude = $_POST['exclude'];
    $duration_hours = (int) $_POST['duration_hours'];
    $reference_code = $_POST['reference_code'];

    // ====== ITINERARY ======
    $itinerary_path = $product['itinerary_file'];

    if (!empty($_FILES['itinerary']['name'])) {
        $dir = UPLOAD_PATH . '/itinerary';
        if (!is_dir($dir))
            mkdir($dir, 0777, true);

        $name = time() . '_' . basename($_FILES['itinerary']['name']);
        move_uploaded_file($_FILES['itinerary']['tmp_name'], $dir . '/' . $name);
        $itinerary_path = UPLOAD_URL . '/itinerary/' . $name;
    }

    // ====== PHOTOS + THUMBNAIL ======
    $thumbnail_path = $product['thumbnail'];

    // Handle deleted photos
    if (!empty($_POST['deleted_photos'])) {
        $deletedPaths = explode(',', $_POST['deleted_photos']);

        foreach ($deletedPaths as $path) {
            $path = trim($path);
            if (empty($path))
                continue;

            // Hapus dari filesystem
            $fs = str_replace(UPLOAD_URL, UPLOAD_PATH, $path);
            if (file_exists($fs)) {
                unlink($fs);
            }

            // Hapus dari database
            $delStmt = $conn->prepare("DELETE FROM product_photos WHERE product_id=? AND photo_path=?");
            $delStmt->bind_param("is", $product_id, $path);
            $delStmt->execute();
            $delStmt->close();

            // Update thumbnail jika yang dihapus adalah thumbnail
            if ($product['thumbnail'] === $path) {
                $thumbnail_path = null;
            }
        }
    }

    // Upload new photos
    if (!empty($_FILES["photos"]["name"][0])) {

        $dir = UPLOAD_PATH . '/photos/';
        if (!is_dir($dir))
            mkdir($dir, 0777, true);

        foreach ($_FILES["photos"]["name"] as $i => $photoName) {
            if ($_FILES["photos"]["error"][$i] === 0) {

                $fileName = time() . "_" . $i . "_" . basename($photoName);
                $targetFs = $dir . $fileName;
                $targetDb = UPLOAD_URL . '/photos/' . $fileName;

                move_uploaded_file($_FILES["photos"]["tmp_name"][$i], $targetFs);

                // Set first uploaded photo as thumbnail if no thumbnail exists
                if (!$thumbnail_path) {
                    $thumbnail_path = $targetDb;
                }

                $stmt4 = $conn->prepare("
                    INSERT INTO product_photos (product_id, photo_path)
                    VALUES (?, ?)
                ");
                $stmt4->bind_param("is", $product_id, $targetDb);
                $stmt4->execute();
                $stmt4->close();
            }
        }
    }

    // If no thumbnail exists, use first available photo
    if (!$thumbnail_path) {
        $thumbQuery = $conn->prepare("SELECT photo_path FROM product_photos WHERE product_id=? LIMIT 1");
        $thumbQuery->bind_param("i", $product_id);
        $thumbQuery->execute();
        $thumbResult = $thumbQuery->get_result();
        if ($thumbRow = $thumbResult->fetch_assoc()) {
            $thumbnail_path = $thumbRow['photo_path'];
        }
        $thumbQuery->close();
    }

    // ====== UPDATE PRODUCT ======
    $stmt = $conn->prepare("
        UPDATE products SET
            title=?,
            full_description=?,
            `include`=?,
            `exclude`=?,
            duration_hours=?,
            reference_code=?,
            itinerary_file=?,
            thumbnail=?,
            updated_at=NOW()
        WHERE id=?
    ");

    $stmt->bind_param(
        "ssssisssi",
        $title,
        $full_desc,
        $include,
        $exclude,
        $duration_hours,
        $reference_code,
        $itinerary_path,
        $thumbnail_path,
        $product_id
    );
    $stmt->execute();
    $stmt->close();

    // ====== PRICES ======
    $conn->query("DELETE FROM product_prices WHERE product_id=$product_id");

    $ins = $conn->prepare("
        INSERT INTO product_prices (product_id, category, adult_price, child_price)
        VALUES (?, ?, ?, ?)
    ");

    $ins->bind_param("isii", $product_id, $cat, $adult, $child);

    $cat = 'private';
    $adult = $_POST['adult_private'];
    $child = $_POST['child_private'];
    $ins->execute();

    $cat = 'group';
    $adult = $_POST['adult_group'];
    $child = $_POST['child_group'];
    $ins->execute();
    $ins->close();

    // ====== CATEGORY MAP ======
    $conn->query("DELETE FROM product_categories WHERE product_id=$product_id");

    if (!empty($_POST['category_ids'])) {
        $ids = array_map('intval', explode(',', $_POST['category_ids']));
        $map = $conn->prepare("INSERT INTO product_categories (product_id, category_id) VALUES (?,?)");
        foreach ($ids as $cid) {
            $map->bind_param("ii", $product_id, $cid);
            $map->execute();
        }
        $map->close();
    }

    header("Location: /PROGNET/admin/home.php?id=$product_id&updated=1");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Edit Product — uRoam</title>

    <link rel="icon" type="image/uroam-icon" href="/PROGNET/images/uroam-title.ico">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Solway:wght@300;400;500;700;800&display=swap" rel="stylesheet">

    <!-- GLOBAL LAYOUT -->
    <link rel="stylesheet" href="/PROGNET/assets/shared/css/style.css">
    <link rel="stylesheet" href="/PROGNET/assets/shared/css/sidebarAdmin.css">
    <link rel="stylesheet" href="/PROGNET/assets/admin/css/sidebarAdmin.css">
    <link rel="stylesheet" href="/PROGNET/assets/admin/css/navbarAdmin.css">

    <!-- PAGE CSS -->
    <link rel="stylesheet" href="/PROGNET/assets/admin/css/create-product.css">
</head>

<body class="hm">
    <?php require_once __DIR__ . '/../_partials/sidebarAdmin.html'; ?>
    <?php require_once __DIR__ . '/../_partials/navbarAdmin.html'; ?>

    <div id="content-wrapper">
        <main class="cp-main">

            <h1 class="cp-title">Edit Product</h1>

            <form class="cp-grid" method="POST" enctype="multipart/form-data">

                <!-- LEFT SIDE -->
                <div class="cp-card cp-left">

                    <div class="cp-field">
                        <label class="cp-label">Title</label>
                        <input type="text" name="title" maxlength="60" class="cp-input"
                            value="<?= htmlspecialchars($product['title']) ?>">
                    </div>

                    <div class="cp-field">
                        <label class="cp-label">Full Description</label>
                        <textarea name="full_description" rows="7"
                            class="cp-textarea"><?= htmlspecialchars($product['full_description']) ?></textarea>
                    </div>

                    <div class="cp-field">
                        <label class="cp-label">Include</label>
                        <textarea name="include" rows="4"
                            class="cp-textarea"><?= htmlspecialchars($product['include']) ?></textarea>
                    </div>

                    <div class="cp-field">
                        <label class="cp-label">Exclude</label>
                        <textarea name="exclude" rows="4"
                            class="cp-textarea"><?= htmlspecialchars($product['exclude']) ?></textarea>
                    </div>

                </div>

                <!-- OPTION CARD -->
                <div class="cp-card cp-option-card">

                    <h2 class="cp-opt-title">Option</h2>

                    <div class="cp-field">
                        <label class="cp-label">Duration Hours</label>
                        <input type="number" name="duration_hours" class="cp-input"
                            value="<?= $product['duration_hours'] ?>">
                    </div>

                    <!-- PRIVATE -->
                    <div class="cp-option-block">
                        <h3 class="cp-opt-subtitle">Customer Category Private</h3>

                        <div class="cp-price-row">
                            <div class="cp-currency">IDR</div>
                            <input type="number" name="adult_private" class="cp-input cp-price"
                                value="<?= $prices['private']['adult_price'] ?>">
                            <span class="cp-age-label">Adult 12+</span>
                        </div>

                        <div class="cp-price-row">
                            <div class="cp-currency">IDR</div>
                            <input type="number" name="child_private" class="cp-input cp-price"
                                value="<?= $prices['private']['child_price'] ?>">
                            <span class="cp-age-label">Child ≤ 12</span>
                        </div>
                    </div>

                    <!-- GROUP -->
                    <div class="cp-option-block">
                        <h3 class="cp-opt-subtitle">Customer Category Group</h3>

                        <div class="cp-price-row">
                            <div class="cp-currency">IDR</div>
                            <input type="number" name="adult_group" class="cp-input cp-price"
                                value="<?= $prices['group']['adult_price'] ?>">
                            <span class="cp-age-label">Adult 12+</span>
                        </div>

                        <div class="cp-price-row">
                            <div class="cp-currency">IDR</div>
                            <input type="number" name="child_group" class="cp-input cp-price"
                                value="<?= $prices['group']['child_price'] ?>">
                            <span class="cp-age-label">Child ≤ 12</span>
                        </div>
                    </div>

                </div>

                <!-- RIGHT SIDE -->
                <div class="cp-card cp-right">
                    <div class="cp-field">
                        <!-- CURRENT ITINERARY -->
                        <label class="cp-label">Current Itinerary File</label>
                        <?php if ($product['itinerary_file']): ?>
                            <p class="cp-note">
                                <a href="<?= $product['itinerary_file'] ?>" target="_blank">View Current PDF</a>
                            </p>
                        <?php else: ?>
                            <p class="cp-note">No itinerary uploaded yet.</p>
                        <?php endif; ?>

                        <!-- UPLOAD NEW ITINERARY -->
                        <label class="cp-label">Upload New Itinerary File</label>
                        <label class="cp-upload">
                            <input type="file" name="itinerary" accept="application/pdf" id="itineraryInput">
                            <div class="cp-upload-box">
                                <img src="/PROGNET/images/icons/upload.svg">
                                <span>Choose PDF</span>
                            </div>
                        </label>
                        <p id="itineraryStatus" class="cp-note"></p>

                        <!-- PHOTOS GRID -->
                        <label class="cp-label" style="margin-top:20px;">Product Photos (Max 4)</label>
                        <!-- UPLOAD NEW PHOTOS -->
                        <label class="cp-label" style="margin-top:20px;">Add Photos</label>

                        <label class="cp-upload" id="uploadContainer">
                            <input type="file" name="photos[]" accept="image/*" multiple id="photoInput">
                            <div class="cp-upload-box" id="uploadBox">
                                <img src="/PROGNET/images/icons/upload.svg">
                                <span>Choose Images</span>
                            </div>
                        </label>

                        <div class="cp-photo-grid" id="photoGrid">
                            <?php foreach ($photos as $idx => $p): ?>
                                <div class="slot" data-type="current" data-path="<?= htmlspecialchars($p['photo_path']) ?>">
                                    <img src="<?= $p['photo_path'] ?>" class="photo-thumb">
                                    <button type="button" class="photo-delete" title="Delete photo">✕</button>
                                </div>
                            <?php endforeach; ?>

                            <?php for ($i = count($photos); $i < 4; $i++): ?>
                                <div class="slot empty"></div>
                            <?php endfor; ?>
                        </div>


                        <p id="photoStatus" class="cp-note"></p>
                    </div>

                    <!-- REFERENCE CODE -->
                    <div class="cp-field" style="margin-top:20px;">
                        <label class="cp-label">Reference Code</label>
                        <input type="text" name="reference_code" class="cp-input"
                            value="<?= htmlspecialchars($product['reference_code']) ?>">
                    </div>

                    <div class="cp-field">
                        <div class="category-wrapper">
                            <h2>Destination Category</h2>

                            <div class="category-list">
                                <button type="button" class="chip chip-all">All Categories</button>
                                <?php foreach ($categories as $cat): ?>
                                    <button type="button"
                                        class="chip <?= in_array($cat['id'], $selectedCats) ? 'active' : '' ?>"
                                        data-id="<?= $cat['id'] ?>"> <?= htmlspecialchars($cat['name']) ?></button>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <input type="hidden" name="category_ids" id="categoryIds"
                            value="<?= implode(',', $selectedCats) ?>">
                    </div>

                    <!-- ACTION BUTTONS -->
                    <div class="cp-actions">
                        <a href="/PROGNET/admin/home.php" class="cp-btn cp-btn-ghost">Cancel</a>
                        <button class="cp-btn cp-btn-primary">Save Changes</button>
                    </div>
                </div>

            </form>

        </main>
    </div>

    <script src="/PROGNET/assets/admin/js/sidebarAdmin.js"></script>
    <script src="/PROGNET/assets/admin/js/navbarAdmin.js"></script>
    <script src="/PROGNET/assets/admin/js/edit.js"></script>

</body>

</html>