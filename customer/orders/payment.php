<?php
require_once __DIR__ . '/../_guards/customerGuard.php';
require_once __DIR__ . '/../../database/connect.php';

// Get customer ID from session
$customer_id = $_SESSION['customer_id'] ?? null;

if (!$customer_id) {
    header('Location: /PROGNET/customer/auth/login.php');
    exit();
}

// Auto-clean stale payment requests (>1 day since last update)
$cleanup = $conn->prepare("DELETE FROM order_request WHERE status = 'payment' AND updated_at < DATE_SUB(NOW(), INTERVAL 1 DAY)");
if ($cleanup) {
    $cleanup->execute();
    $cleanup->close();
}

// Get order requests from this customer waiting for payment
$query = "
    SELECT 
        o.booking_code,
        o.product_id,
        o.option_name,
        o.customer_name,
        o.total_adult,
        o.total_child,
        o.gross_rate,
        o.purchase_date,
        o.activity_date,
        o.updated_at,
        o.created_at,
        p.title AS product_title,
        p.duration_hours AS duration,
        p.thumbnail AS thumb
    FROM order_request o
    JOIN products p ON p.id = o.product_id
    WHERE o.customer_id = ? AND o.status = 'payment'
    ORDER BY o.created_at DESC
";

$stmt = $conn->prepare($query);
$stmt->bind_param('i', $customer_id);
$stmt->execute();
$orders = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Payment - uRoam</title>

    <link rel="icon" type="image/uroam-icon" href="/PROGNET/images/uroam-title.ico">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Solway:wght@300;400;500;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="/PROGNET/assets/shared/css/style.css">
    <link rel="stylesheet" href="/PROGNET/assets/admin/css/sidebarAdmin.css">
    <link rel="stylesheet" href="/PROGNET/assets/customer/css/navbar.css">
    <link rel="stylesheet" href="/PROGNET/assets/customer/css/footer.css">
    <link rel="stylesheet" href="/PROGNET/assets/admin/css/bookings.css">
    <link rel="stylesheet" href="/PROGNET/assets/customer/css/order-payment.css">
    <style>
        .bk-actions {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .bk-expire-row {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            background-color: #fee2e2;
            border: 1px solid #fca5a5;
            border-radius: 6px;
            color: #991b1b;
            font-size: 13px;
            font-weight: 500;
        }

        .bk-expire-row .countdown {
            color: #dc2626;
            font-weight: 700;
            font-size: 14px;
            font-family: 'Courier New', monospace;
        }
    </style>
</head>

<body>
    <!-- Sidebar -->
    <?php require_once __DIR__ . '/../_partials/sidebarCustomer.html'; ?>

    <!-- Navbar -->
    <?php require_once __DIR__ . '/../_partials/navbar.php'; ?>

    <div class="content-wrapper">
        <main class="hm-main order-payment-container">
            <section class="bk-hero">
                <h1 class="bk-title">Order Payment</h1>

                <!-- ORDERS LIST -->
                <section class="bk-list">
                    <?php if ($orders->num_rows > 0): ?>
                        <?php while ($o = $orders->fetch_assoc()): ?>
                            <article class="bk-card payment-card">
                                <img class="bk-thumb" src="<?= $o['thumb'] ?: '/PROGNET/images/no-photo.png' ?>">

                                <div class="bk-info">
                                    <h3 class="bk-item-title">
                                        <span class="bk-title-text">
                                            <?= htmlspecialchars($o['product_title']) ?>
                                        </span>

                                        <span class="bk-purchase-date">
                                            Requested on <?= date('d-m-Y (H:i)', strtotime($o['created_at'])) ?>
                                        </span>
                                    </h3>

                                    <p class="bk-item-sub">Option: <?= htmlspecialchars($o['option_name']) ?></p>

                                    <div class="bk-meta-row">
                                        <span>Activity date: <?= date('d-m-Y (H:i)', strtotime($o['activity_date'])) ?></span>
                                        <span><?= $o['booking_code'] ?></span>
                                        <span>
                                            <?= ($o['total_adult'] + $o['total_child']) ?> people -
                                            IDR <?= number_format($o['gross_rate']) ?>
                                        </span>
                                    </div>

                                    <div class="bk-actions">
                                        <button class="bk-detail-btn">Show details</button>
                                        <?php $expireAt = $o['updated_at'] ? date('c', strtotime($o['updated_at'] . ' +1 day')) : ''; ?>
                                        <div class="bk-expire-row" data-expiry="<?= htmlspecialchars($expireAt) ?>">
                                            <span>Auto delete order in</span>
                                            <strong class="countdown">--:--:--</strong>
                                        </div>
                                        <a href="/PROGNET/customer/orders/order-pay.php?booking_code=<?= htmlspecialchars($o['booking_code']) ?>"
                                            class="pay-btn">Pay Now</a>
                                    </div>

                                    <!-- DETAIL DROPDOWN -->
                                    <div class="bk-detail-box">
                                        <div class="bk-detail-row">
                                            <span class="bk-label">Total Adult</span>
                                            <span class="bk-value"><?= $o['total_adult'] ?></span>
                                        </div>

                                        <div class="bk-detail-row">
                                            <span class="bk-label">Total Child</span>
                                            <span class="bk-value"><?= $o['total_child'] ?></span>
                                        </div>

                                        <div class="bk-detail-row">
                                            <span class="bk-label">Gross Rate</span>
                                            <span class="bk-value">IDR <?= number_format($o['gross_rate']) ?></span>
                                        </div>

                                        <div class="bk-detail-row">
                                            <span class="bk-label">Duration</span>
                                            <span class="bk-value"><?= $o['duration'] ?> hours</span>
                                        </div>
                                    </div>
                                </div>

                            </article>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="bk-empty">
                            <p>No pending payments. All set!</p>
                            <a href="/PROGNET/customer/tours.php" class="bk-empty-btn">Browse Tours</a>
                        </div>
                    <?php endif; ?>
                </section>
            </section>
        </main>
    </div>

    <!-- Footer -->
    <?php require_once __DIR__ . '/../_partials/footer.php'; ?>

    <script>
        (function () {
            const rows = document.querySelectorAll('.bk-expire-row');
            const pad = (n) => String(n).padStart(2, '0');
            function render(row, ms) {
                const span = row.querySelector('.countdown');
                if (!span) return;
                if (ms <= 0) {
                    span.textContent = 'Expired';
                    return;
                }
                const totalSeconds = Math.floor(ms / 1000);
                const h = Math.floor(totalSeconds / 3600);
                const m = Math.floor((totalSeconds % 3600) / 60);
                const s = totalSeconds % 60;
                span.textContent = `${pad(h)}:${pad(m)}:${pad(s)}`;
            }
            function tick() {
                const now = Date.now();
                rows.forEach(row => {
                    const expiry = row.getAttribute('data-expiry');
                    if (!expiry) {
                        render(row, 0);
                        return;
                    }
                    const msLeft = Date.parse(expiry) - now;
                    render(row, msLeft);
                });
            }
            tick();
            setInterval(tick, 1000);
        })();
    </script>

    <script src="/PROGNET/assets/admin/js/sidebarAdmin.js"></script>
    <script src="/PROGNET/assets/admin/js/bookings.js"></script>
</body>

</html>