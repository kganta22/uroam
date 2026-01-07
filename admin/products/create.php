<?php
require_once __DIR__ . '/../init.php';

$categories = [];
$result = $conn->query("
    SELECT id, name, slug
    FROM categories
    ORDER BY name ASC
");

while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $category_ids = [];

    if (!empty($_POST['category_ids'])) {
        $category_ids = array_map('intval', explode(',', $_POST['category_ids']));
    }

    $title = $_POST['title'];
    $full_desc = $_POST['full_description'];
    $include = $_POST['include'];
    $exclude = $_POST['exclude'];
    $duration_hours = $_POST['duration_hours'];
    $adult_private = $_POST['adult_private'];
    $child_private = $_POST['child_private'];
    $adult_group = $_POST['adult_group'];
    $child_group = $_POST['child_group'];
    $reference_code = $_POST['reference_code'];

    // Upload itinerary file
    $itinerary_path = null;

    if (!empty($_FILES["itinerary"]["name"])) {
        $itinerary_dir = UPLOAD_PATH . '/itinerary';
        if (!is_dir($itinerary_dir)) {
            mkdir($itinerary_dir, 0777, true);
        }

        $fileName = time() . '_' . basename($_FILES['itinerary']['name']);
        $itinerary_fs_path = $itinerary_dir . '/' . $fileName;
        $itinerary_db_path = UPLOAD_URL . '/itinerary/' . $fileName;

        move_uploaded_file($_FILES['itinerary']['tmp_name'], $itinerary_fs_path);

        $itinerary_path = $itinerary_db_path;
    }

    // Upload photos
    $photo_paths = [];
    $thumbnail_path = null;

    if (!empty($_FILES["photos"]["name"][0])) {
        for ($i = 0; $i < count($_FILES["photos"]["name"]); $i++) {
            if ($_FILES["photos"]["error"][$i] === 0) {

                $photo_dir = UPLOAD_PATH . '/photos';
                if (!is_dir($photo_dir)) {
                    mkdir($photo_dir, 0777, true);
                }

                $fileName = time() . '_' . basename($_FILES['photos']['name'][$i]);
                $photo_fs_path = $photo_dir . '/' . $fileName;
                $photo_db_path = UPLOAD_URL . '/photos/' . $fileName;

                move_uploaded_file($_FILES['photos']['tmp_name'][$i], $photo_fs_path);

                // FOTO PERTAMA = THUMBNAIL
                if ($i === 0) {
                    $thumbnail_path = $photo_db_path;
                }

                $photo_paths[] = $photo_db_path;
            }
        }
    }

    $stmt = $conn->prepare("
        INSERT INTO products 
        (title, full_description, include, exclude, duration_hours,
        reference_code, itinerary_file, thumbnail)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $duration_hours = (int) $duration_hours;
    $adult_private = (int) $adult_private;
    $child_private = (int) $child_private;
    $adult_group = (int) $adult_group;
    $child_group = (int) $child_group;

    $stmt->bind_param("ssssisss", $title, $full_desc, $include, $exclude, $duration_hours, $reference_code, $itinerary_path, $thumbnail_path);
    $stmt->execute();
    $product_id = $stmt->insert_id;
    $stmt->close();

    if (!empty($category_ids)) {
        $stmtCat = $conn->prepare("
        INSERT INTO product_categories (product_id, category_id)
        VALUES (?, ?)
    ");

        foreach ($category_ids as $cat_id) {
            $stmtCat->bind_param("ii", $product_id, $cat_id);
            $stmtCat->execute();
        }

        $stmtCat->close();
    }

    $stmt2 = $conn->prepare("
        INSERT INTO product_prices (product_id, category, adult_price, child_price)
        VALUES (?, 'private', ?, ?)
    ");

    $stmt2->bind_param("iii", $product_id, $adult_private, $child_private);
    $stmt2->execute();
    $stmt2->close();

    $stmt3 = $conn->prepare("
        INSERT INTO product_prices (product_id, category, adult_price, child_price)
        VALUES (?, 'group', ?, ?)
    ");

    $stmt3->bind_param("iii", $product_id, $adult_group, $child_group);
    $stmt3->execute();
    $stmt3->close();

    if (!empty($photo_paths)) {
        $stmt4 = $conn->prepare("INSERT INTO product_photos (product_id, photo_path) VALUES (?, ?)");

        foreach ($photo_paths as $path) {
            $stmt4->bind_param("is", $product_id, $path);
            $stmt4->execute();
        }

        $stmt4->close();
    }

    $conn->close();
    // redirect setelah sukses
    header("Location: /PROGNET/admin/home.php?success=1");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Create Product — uRoam</title>

    <link rel="icon" type="image/uroam-icon" href="/PROGNET/images/uroam-title.ico">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Solway:wght@300;400;500;700;800&display=swap" rel="stylesheet">

    <!-- GLOBAL LAYOUT -->
    <link rel="stylesheet" href="/PROGNET/assets/shared/css/style.css">
    <link rel="stylesheet" href="/PROGNET/assets/admin/css/sidebarAdmin.css">
    <link rel="stylesheet" href="/PROGNET/assets/admin/css/navbarAdmin.css">

    <!-- PAGE-ONLY CSS -->
    <link rel="stylesheet" href="/PROGNET/assets/admin/css/create-product.css">
</head>

<body class="hm">
    <?php require_once __DIR__ . '/../_partials/sidebarAdmin.html'; ?>
    <?php require_once __DIR__ . '/../_partials/navbarAdmin.html'; ?>

    <div id="content-wrapper">
        <main class="cp-main">

            <h1 class="cp-title">Create New Product</h1>

            <form class="cp-grid" method="POST" action="create.php" enctype="multipart/form-data">


                <!-- LEFT COLUMN -->
                <div class="cp-card cp-left">
                    <div class="cp-field">
                        <label class="cp-label">Title</label>
                        <input type="text" name="title" maxlength="60" class="cp-input" required>
                    </div>

                    <div class="cp-field">
                        <label class="cp-label">Full Description</label>
                        <textarea name="full_description" rows="7" class="cp-textarea" required></textarea>
                    </div>

                    <div class="cp-field">
                        <label class="cp-label">Include</label>
                        <textarea name="include" rows="4" class="cp-textarea" required></textarea>
                    </div>

                    <div class="cp-field">
                        <label class="cp-label">Exclude</label>
                        <textarea name="exclude" rows="4" class="cp-textarea" required></textarea>
                    </div>
                </div> <!-- END LEFT CARD -->

                <!-- OPTION CARD (NEW) -->
                <div class="cp-card cp-option-card">

                    <h2 class="cp-opt-title">Option</h2>

                    <!-- Duration -->
                    <div class="cp-field">
                        <label class="cp-label">Duration Hours</label>
                        <input type="number" name="duration_hours" min="1" class="cp-input cp-duration" required>
                    </div>

                    <!-- PRIVATE CATEGORY -->
                    <div class="cp-option-block">
                        <h3 class="cp-opt-subtitle">Customer Category Private</h3>

                        <div class="cp-price-row">
                            <label class="cp-label-sm">Price</label>

                            <div class="cp-currency">IDR</div>
                            <input type="number" name="adult_private" class="cp-input cp-price" required>
                            <span class="cp-age-label">Adult 12+</span>
                        </div>

                        <div class="cp-price-row">
                            <label class="cp-label-sm cp-label-empty">Price</label>

                            <div class="cp-currency">IDR</div>
                            <input type="number" name="child_private" class="cp-input cp-price" required>
                            <span class="cp-age-label">Child ≤ 12</span>
                        </div>
                    </div>

                    <!-- GROUP CATEGORY -->
                    <div class="cp-option-block">
                        <h3 class="cp-opt-subtitle">Customer Category Group</h3>

                        <div class="cp-price-row">
                            <label class="cp-label-sm">Price</label>

                            <div class="cp-currency">IDR</div>
                            <input type="number" name="adult_group" class="cp-input cp-price" required>
                            <span class="cp-age-label">Adult 12+</span>
                        </div>

                        <div class="cp-price-row">
                            <label class="cp-label-sm cp-label-empty">Price</label>

                            <div class="cp-currency">IDR</div>
                            <input type="number" name="child_group" class="cp-input cp-price" required>
                            <span class="cp-age-label">Child ≤ 12</span>
                        </div>
                    </div>

                </div>

                <!-- RIGHT COLUMN -->
                <div class="cp-card cp-right">

                    <div class="cp-field">
                        <label class="cp-label">Itinerary File</label>
                        <label class="cp-upload">
                            <input type="file" id="itineraryInput" name="itinerary" accept="application/pdf" required>
                            <div class="cp-upload-box">
                                <img src="/PROGNET/images/icons/upload.svg" alt="">
                                <span>Upload Itinerary File (pdf)</span>
                            </div>
                        </label>

                        <p id="itineraryStatus" class="cp-note"></p>

                        <label class="cp-label">Photos</label>

                        <label class="cp-upload" id="uploadContainer">
                            <input type="file" id="photoInput" name="photos[]" accept="image/*" multiple required>
                            <div class="cp-upload-box">
                                <img src="/PROGNET/images/icons/upload.svg" alt="">
                                <span>Upload Photos</span>
                            </div>
                        </label>

                        <p id="photoStatus" class="cp-note"></p>

                        <div class="cp-photo-grid" id="photoGrid">
                            <div class="slot empty"></div>
                            <div class="slot empty"></div>
                            <div class="slot empty"></div>
                            <div class="slot empty"></div>
                        </div>
                    </div>

                    <div class="cp-field">
                        <label class="cp-label">Reference Code</label>
                        <input type="text" name="reference_code" class="cp-input" required>
                    </div>

                    <div class="cp-field">
                        <div class="category-wrapper">
                            <h2>Destination Category</h2>

                            <div class="category-list">
                                <div class="category-list">
                                    <button type="button" class="chip chip-all" data-all="1">All Categories</button>
                                    <?php foreach ($categories as $cat): ?>
                                        <button type="button" class="chip" data-id="<?= $cat['id'] ?>">
                                            <?= htmlspecialchars($cat['name']) ?> </button>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="category_ids" id="categoryIds">
                    </div>

                    <!-- Actions -->
                    <div class="cp-actions">
                        <a href="/PROGNET/admin/home.php" class="cp-btn cp-btn-ghost">Cancel</a>
                        <button class="cp-btn cp-btn-primary">Save Product</button>
                    </div>
                </div>
            </form>
        </main>
    </div>

    <!-- JS -->
    <script src="/PROGNET/assets/admin/js/sidebarAdmin.js"></script>
    <script src="/PROGNET/assets/admin/js/navbarAdmin.js"></script>
    <script src="/PROGNET/assets/admin/js/create-product.js"></script>

</body>

</html>