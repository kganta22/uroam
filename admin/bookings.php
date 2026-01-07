<?php
require_once __DIR__ . '/init.php';

$products = [];
$res = $conn->query("SELECT id, title FROM products ORDER BY title ASC");
while ($row = $res->fetch_assoc()) {
    $products[] = $row;
}

$admins = [];
$resA = $conn->query("SELECT id, full_name FROM admin ORDER BY full_name ASC");
while ($a = $resA->fetch_assoc()) {
    $admins[] = $a;
}

$where = [];
$params = [];
$types = "";

if (!empty($_GET['products'])) {
    $ids = $_GET['products']; // array
    $placeholders = implode(",", array_fill(0, count($ids), "?"));
    $where[] = "product_id IN ($placeholders)";
    foreach ($ids as $id) {
        $params[] = $id;
        $types .= "i";
    }
}

if (!empty($_GET['purchase_start'])) {
    $where[] = "purchase_date >= ?";
    $params[] = $_GET['purchase_start'];
    $types .= "s";
}

if (!empty($_GET['purchase_end'])) {
    $where[] = "purchase_date <= ?";
    $params[] = $_GET['purchase_end'];
    $types .= "s";
}

if (!empty($_GET['activity_start'])) {
    $where[] = "activity_date >= ?";
    $params[] = $_GET['activity_start'];
    $types .= "s";
}

if (!empty($_GET['activity_end'])) {
    $where[] = "activity_date <= ?";
    $params[] = $_GET['activity_end'];
    $types .= "s";
}

$whereSQL = "";
if (!empty($where)) {
    $whereSQL = "WHERE " . implode(" AND ", $where);
}

$query = "
    SELECT 
        b.booking_code,
        b.product_id,
        b.option_name,
        b.customer_name,

        b.total_adult,
        b.total_child,

        b.gross_rate,
        b.discount_rate,
        b.net_rate,

        b.duration,
        b.meeting_point,
        b.phone,
        b.email,

        b.purchase_date,
        b.activity_date,
        b.created_at,

        p.title AS product_title,
        (SELECT photo_path 
         FROM product_photos 
         WHERE product_id = b.product_id 
         LIMIT 1) AS thumb
    FROM bookings b
    JOIN products p ON p.id = b.product_id
    $whereSQL
    ORDER BY b.created_at DESC
";

$stmt = $conn->prepare($query);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$bookings = $stmt->get_result();

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bookings — Supplier Portal</title>

    <link rel="icon" type="image/uroam-icon" href="/PROGNET/images/uroam-title.ico">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Solway:wght@300;400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="/PROGNET/assets/shared/css/style.css">
    <link rel="stylesheet" href="/PROGNET/assets/shared/css/filter-product.css">
    <link rel="stylesheet" href="/PROGNET/assets/admin/css/sidebarAdmin.css">
    <link rel="stylesheet" href="/PROGNET/assets/admin/css/navbarAdmin.css">
    <link rel="stylesheet" href="/PROGNET/assets/admin/css/bookings.css">
</head>

<body class="hm">

    <?php require_once __DIR__ . '/_partials/sidebarAdmin.html'; ?>
    <?php require_once __DIR__ . '/_partials/navbarAdmin.html'; ?>

    <div class="content-wrapper">
        <main class="hm-main">

            <section class="bk-hero">
                <h1 class="bk-title">Bookings</h1>

                <div class="bk-layout">

                    <!-- FILTER PANEL -->
                    <aside class="bk-filters">
                        <div class="bk-filters-header">
                            <span class="bk-filters-title">Filters</span>
                        </div>

                        <form id="filterForm" method="GET">
                            <!-- PRODUCT DROPDOWN FILTER -->
                            <?php require_once __DIR__ . '/../assets/components/filter-product.php'; ?>

                            <!-- PURCHASE DATE FILTER -->
                            <div class="bk-filter-block bk-date-block">
                                <div class="bk-filter-header">
                                    <label class="bk-label bk-filter-label">Purchase Date</label>

                                    <a class="bk-clear" onclick="clearDateFilter('purchase')"
                                        style="display: <?= (!empty($_GET['purchase_start']) || !empty($_GET['purchase_end'])) ? 'inline' : 'none' ?>;">
                                        Clear
                                    </a>
                                </div>

                                <div class="bk-date-input">
                                    <input type="text" id="purchaseRange" class="bk-input bk-filter-input"
                                        placeholder="From - To" readonly>

                                    <img src="/PROGNET/images/icons/calendar.svg" class="bk-calendar-icon"
                                        alt="calendar">
                                </div>

                                <!-- hidden input untuk PHP -->
                                <input type="hidden" name="purchase_start" id="purchase_start"
                                    value="<?= $_GET['purchase_start'] ?? '' ?>">

                                <input type="hidden" name="purchase_end" id="purchase_end"
                                    value="<?= $_GET['purchase_end'] ?? '' ?>">
                            </div>

                            <!-- ACTIVITY DATE FILTER -->
                            <div class="bk-filter-block bk-date-block">
                                <div class="bk-filter-block bk-date-block">
                                    <div class="bk-filter-header">
                                        <label class="bk-label bk-filter-label">Activity Date</label>

                                        <a class="bk-clear" onclick="clearDateFilter('activity')"
                                            style="display: <?= (!empty($_GET['activity_start']) || !empty($_GET['activity_end'])) ? 'block' : 'none' ?>;">
                                            Clear
                                        </a>
                                    </div>

                                    <div class="bk-date-input">
                                        <input type="text" id="activityRange" class="bk-input bk-filter-input"
                                            placeholder="From - To" readonly>

                                        <img src="/PROGNET/images/icons/calendar.svg" class="bk-calendar-icon"
                                            alt="calendar">
                                    </div>

                                    <!-- hidden input untuk PHP -->
                                    <input type="hidden" name="activity_start" id="activity_start"
                                        value="<?= $_GET['activity_start'] ?? '' ?>">

                                    <input type="hidden" name="activity_end" id="activity_end"
                                        value="<?= $_GET['activity_end'] ?? '' ?>">



                                </div>
                        </form>

                    </aside>
                    <!-- BOOKINGS LIST -->
                    <section class="bk-list">

                        <?php while ($b = $bookings->fetch_assoc()): ?>

                            <article class="bk-card">

                                <img class="bk-thumb" src="<?= $b['thumb'] ?: '../images/no-photo.svg' ?>">

                                <div class="bk-info">

                                    <h3 class="bk-item-title">
                                        <span class="bk-title-text">
                                            <?= htmlspecialchars($b['product_title']) ?>
                                        </span>

                                        <span class="bk-purchase-date">
                                            Purchased on <?= $b['purchase_date'] ?>
                                        </span>
                                    </h3>

                                    <p class="bk-item-sub">Option: <?= htmlspecialchars($b['option_name']) ?></p>

                                    <div class="bk-meta-row">
                                        <span>Activity date:
                                            <?= $b['activity_date'] ? date('d-m-Y (H:i)', strtotime($b['activity_date'])) : '-' ?></span>
                                        <span><?= $b['booking_code'] ?></span>
                                        <span>
                                            <?= ($b['total_adult'] + $b['total_child']) ?> people -
                                            IDR <?= number_format($b['net_rate']) ?>
                                        </span>
                                    </div>

                                    <div class="bk-actions">
                                        <button class="bk-detail-btn">Show details</button>
                                    </div>

                                    <!-- DETAIL DROPDOWN -->
                                    <div class="bk-detail-box">
                                        <div class="bk-detail-row">
                                            <span class="bk-label">Customer Name</span>
                                            <span class="bk-value"><?= htmlspecialchars($b['customer_name']) ?></span>
                                        </div>

                                        <div class="bk-detail-row">
                                            <span class="bk-label">Total Adult</span>
                                            <span class="bk-value"><?= $b['total_adult'] ?></span>
                                        </div>

                                        <div class="bk-detail-row">
                                            <span class="bk-label">Total Child</span>
                                            <span class="bk-value"><?= $b['total_child'] ?></span>
                                        </div>

                                        <div class="bk-detail-row">
                                            <span class="bk-label">Gross Rate</span>
                                            <span class="bk-value">IDR <?= number_format($b['gross_rate']) ?></span>
                                        </div>

                                        <div class="bk-detail-row">
                                            <span class="bk-label">Discount Rate</span>
                                            <span class="bk-value">IDR <?= number_format($b['discount_rate']) ?></span>
                                        </div>

                                        <div class="bk-detail-row">
                                            <span class="bk-label">Net Rate</span>
                                            <span class="bk-value">IDR <?= number_format($b['net_rate']) ?></span>
                                        </div>

                                        <div class="bk-detail-row">
                                            <span class="bk-label">Earned Points</span>
                                            <span class="bk-value"><?= number_format((int) ($b['net_rate'] * 0.13)) ?>
                                                points</span>
                                        </div>

                                        <div class="bk-detail-row">
                                            <span class="bk-label">Duration</span>
                                            <span class="bk-value"><?= $b['duration'] ?> hours</span>
                                        </div>

                                        <div class="bk-detail-row">
                                            <span class="bk-label">Meeting Point</span>
                                            <span class="bk-value"><?= htmlspecialchars($b['meeting_point']) ?></span>
                                        </div>

                                        <div class="bk-detail-row">
                                            <span class="bk-label">Email</span>
                                            <span class="bk-value"><?= htmlspecialchars($b['email']) ?></span>
                                        </div>

                                        <div class="bk-detail-row">
                                            <span class="bk-label">Phone</span>
                                            <span class="bk-value"><?= htmlspecialchars($b['phone']) ?></span>
                                        </div>
                                    </div>
                                </div>
                            </article>

                        <?php endwhile; ?>

                    </section>

                </div>
            </section>

        </main>
    </div>

    <script src="/PROGNET/assets/shared/js/filter-product.js"></script>
    <script src="/PROGNET/assets/admin/js/sidebarAdmin.js"></script>
    <script src="/PROGNET/assets/admin/js/navbarAdmin.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="/PROGNET/assets/admin/js/bookings.js"></script>
</body>

</html>