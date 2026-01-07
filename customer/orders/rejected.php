<?php
require_once __DIR__ . '/../_guards/customerGuard.php';
require_once __DIR__ . '/../../database/connect.php';

// Get customer ID from session
$customer_id = $_SESSION['customer_id'] ?? null;

if (!$customer_id) {
    header('Location: /PROGNET/customer/auth/login.php');
    exit();
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
        b.purchase_date,
        b.activity_date,
        b.created_at,
        p.title AS product_title,
        p.duration_hours AS duration,
        c.reason AS reject_reason,
        a.full_name AS rejected_by_name,
        p.thumbnail AS thumb
    FROM order_request b
    JOIN products p ON p.id = b.product_id
    JOIN order_cancellations c ON c.booking_code = b.booking_code
    JOIN admin a ON a.id = c.admin_id
    WHERE b.customer_id = ? AND b.status = 'reject'
    ORDER BY b.created_at DESC
";

$stmt = $conn->prepare($query);
$stmt->bind_param('i', $customer_id);
$stmt->execute();
$bookings = $stmt->get_result();

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rejected Orders - uRoam</title>

    <link rel="icon" type="image/uroam-icon" href="/PROGNET/images/uroam-title.ico">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Solway:wght@300;400;500;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="/PROGNET/assets/shared/css/style.css">
    <link rel="stylesheet" href="/PROGNET/assets/admin/css/sidebarAdmin.css">
    <link rel="stylesheet" href="/PROGNET/assets/customer/css/navbar.css">
    <link rel="stylesheet" href="/PROGNET/assets/customer/css/footer.css">
    <link rel="stylesheet" href="/PROGNET/assets/admin/css/bookings.css">
    <link rel="stylesheet" href="/PROGNET/assets/admin/css/order-reject.css">
    <link rel="stylesheet" href="/PROGNET/assets/customer/css/order-rejected.css">
</head>

<body>
    <!-- Sidebar -->
    <?php require_once __DIR__ . '/../_partials/sidebarCustomer.html'; ?>

    <!-- Navbar -->
    <?php require_once __DIR__ . '/../_partials/navbar.php'; ?>

    <div class="content-wrapper">
        <main class="hm-main order-rejected-container">

            <section class="bk-hero">
                <h1 class="bk-title">Rejected Orders</h1>

                <div class="bk-layout">
                    <!-- BOOKINGS LIST -->
                    <section class="bk-list">

                        <?php if ($bookings->num_rows > 0): ?>
                            <?php while ($b = $bookings->fetch_assoc()): ?>

                                <article class="bk-card">

                                    <img class="bk-thumb" src="<?= $b['thumb'] ?: '/PROGNET/images/no-photo.png' ?>">

                                    <div class="bk-info">

                                        <h3 class="bk-item-title">
                                            <span class="bk-title-text">
                                                <?= htmlspecialchars($b['product_title']) ?>
                                            </span>

                                            <span class="bk-purchase-date">
                                                Requested on <?= date('d-m-Y (H:i)', strtotime($b['created_at'])) ?>
                                            </span>
                                        </h3>

                                        <p class="bk-item-sub">Option: <?= htmlspecialchars($b['option_name']) ?></p>

                                        <div class="bk-meta-row">
                                            <span>Activity date:
                                                <?= date('d-m-Y (H:i)', strtotime($b['activity_date'])) ?></span>
                                            <span><?= $b['booking_code'] ?></span>
                                            <span>
                                                <?= ($b['total_adult'] + $b['total_child']) ?> people -
                                                IDR <?= number_format($b['gross_rate']) ?>
                                            </span>
                                        </div>

                                        <div class="bk-actions">
                                            <button class="bk-detail-btn">Show details</button>
                                            <button class="bk-reject-btn">Rejected</button>
                                        </div>

                                        <!-- DETAIL DROPDOWN -->
                                        <div class="bk-detail-box">
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
                                                <span class="bk-label">Duration</span>
                                                <span class="bk-value"><?= $b['duration'] ?> hours</span>
                                            </div>
                                        </div>

                                        <!-- REJECT INFO PANEL (READ ONLY) -->
                                        <div class="bk-reject-info">
                                            <h4 class="bk-reject-title">Rejected Information</h4>

                                            <div class="bk-reject-row">
                                                <span class="bk-reject-label">Rejected by</span>
                                                <span class="bk-reject-value">
                                                    <?= htmlspecialchars($b['rejected_by_name'] ?? '-') ?>
                                                </span>
                                            </div>

                                            <div class="bk-reject-row">
                                                <span class="bk-reject-label">Reason</span>
                                                <div class="bk-reject-reason">
                                                    <?= nl2br(htmlspecialchars($b['reject_reason'])) ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </article>

                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="bk-empty">
                                <p>No rejected orders.</p>
                                <a href="/PROGNET/customer/tours.php" class="bk-empty-btn">Browse Tours</a>
                            </div>
                        <?php endif; ?>

                    </section>

                </div>
            </section>

        </main>
    </div>

    <!-- Footer -->
    <?php require_once __DIR__ . '/../_partials/footer.php'; ?>

    <script src="/PROGNET/assets/admin/js/sidebarAdmin.js"></script>
    <script src="/PROGNET/assets/admin/js/bookings.js"></script>

</body>

</html>