<?php
require_once __DIR__ . '/../init.php';

if (isset($_POST['accept_booking'])) {

    $bookingCode = $_POST['booking_code'] ?? '';

    if ($bookingCode === '') {
        header("Location: /PROGNET/admin/orders/reviews.php?error=invalid");
        exit;
    }

    $stmt = $conn->prepare("
        UPDATE order_request
        SET status = 'payment',
            updated_at = NOW()
        WHERE booking_code = ?
    ");
    $stmt->bind_param("s", $bookingCode);
    $stmt->execute();
    $stmt->close();

    header("Location: /PROGNET/admin/orders/reviews.php?success=accepted");
    exit;
}

if (isset($_POST['reject_booking'])) {

    $bookingCode = $_POST['booking_code'] ?? '';
    $adminId = intval($_POST['admin_id']);
    $reason = trim($_POST['reject_reason']);

    if ($bookingCode === '' || $adminId <= 0 || strlen($reason) < 15) {
        header("Location: /PROGNET/admin/orders/reviews.php?error=invalid");
        exit;
    }

    try {

        $conn->begin_transaction();

        $stmt1 = $conn->prepare("
            UPDATE order_request
            SET status = 'reject',
                updated_at = NOW()
            WHERE booking_code = ?
        ");
        $stmt1->bind_param("s", $bookingCode);
        $stmt1->execute();
        $stmt1->close();

        $stmt2 = $conn->prepare("
            INSERT INTO order_cancellations
                (booking_code, admin_id, reason)
            VALUES (?, ?, ?)
        ");
        $stmt2->bind_param("sis", $bookingCode, $adminId, $reason);
        $stmt2->execute();
        $stmt2->close();

        $conn->commit();

        header("Location: /PROGNET/admin/orders/reviews.php?success=rejected");
        exit;

    } catch (Exception $e) {

        $conn->rollback();
        header("Location: /PROGNET/admin/orders/reviews.php?error=failed");
        exit;
    }
}

$admins = [];
$resA = $conn->query("SELECT id, full_name FROM admin ORDER BY full_name ASC");
while ($a = $resA->fetch_assoc()) {
    $admins[] = $a;
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
        (SELECT photo_path FROM product_photos 
            WHERE product_id = b.product_id 
            LIMIT 1) AS thumb
    FROM order_request b
    JOIN products p ON p.id = b.product_id
    WHERE b.status = 'request'
    ORDER BY b.created_at DESC
";

$stmt = $conn->prepare($query);
$stmt->execute();
$bookings = $stmt->get_result();

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Request — Supplier Portal</title>

    <link rel="icon" type="image/uroam-icon" href="/PROGNET/images/uroam-title.ico">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Solway:wght@300;400;500;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="/PROGNET/assets/shared/css/style.css">
    <link rel="stylesheet" href="/PROGNET/assets/admin/css/sidebarAdmin.css">
    <link rel="stylesheet" href="/PROGNET/assets/admin/css/navbarAdmin.css">
    <link rel="stylesheet" href="/PROGNET/assets/admin/css/bookings.css">
    <link rel="stylesheet" href="/PROGNET/assets/admin/css/order-request.css">
</head>

<body class="hm">
    <?php require_once __DIR__ . '/../_partials/sidebarAdmin.html'; ?>
    <?php require_once __DIR__ . '/../_partials/navbarAdmin.html'; ?>

    <div id="content-wrapper">
        <main class="hm-main">

            <section class="bk-hero">
                <h1 class="bk-title">Order Reviews</h1>

                <div class="bk-layout">

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
                                            Requested on <?= date('d-m-Y (H:i)', strtotime($b['created_at'])) ?>
                                        </span>
                                    </h3>

                                    <p class="bk-item-sub">Option: <?= htmlspecialchars($b['option_name']) ?></p>

                                    <div class="bk-meta-row">
                                        <span>Activity date:
                                            <?= $b['activity_date'] ? date('d-m-Y (H:i)', strtotime($b['activity_date'])) : '-' ?></span>
                                        <span><?= $b['booking_code'] ?></span>
                                        <span>
                                            <?= ($b['total_adult'] + $b['total_child']) ?> people -
                                            IDR <?= number_format($b['gross_rate']) ?>
                                        </span>
                                    </div>

                                    <div class="bk-actions">
                                        <button class="bk-detail-btn">Show details</button>
                                        <div class="bk-actions-right">
                                            <button class="bk-accept-btn"
                                                onclick="openAcceptModal('<?= $b['booking_code'] ?>')">Accept</button>
                                            <button class="bk-reject-btn"
                                                data-code="<?= $b['booking_code'] ?>">Reject</button>
                                        </div>
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
                                            <span class="bk-label">Duration</span>
                                            <span class="bk-value"><?= $b['duration'] ?> hours</span>
                                        </div>
                                    </div>

                                    <!-- REJECT PANEL -->
                                    <div class="bk-reject-box">
                                        <form method="POST">

                                            <h4 class="bk-reject-title">Reject Booking</h4>

                                            <!-- booking id (primary key) -->
                                            <input type="hidden" name="booking_code" value="<?= $b['booking_code'] ?>">

                                            <label class="bk-label">Booking Code</label>
                                            <input class="bk-input" value="<?= $b['booking_code'] ?>" readonly>

                                            <label class="bk-label">Admin Name</label>
                                            <select class="bk-input" name="admin_id" required>
                                                <option value="">Select admin</option>
                                                <?php foreach ($admins as $ad): ?>
                                                    <option value="<?= $ad['id'] ?>">
                                                        <?= htmlspecialchars($ad['full_name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>

                                            <label class="bk-label">Reason (min 15 chars)</label>
                                            <textarea class="bk-input" name="reject_reason" minlength="15"
                                                required></textarea>
                                            <button type="submit" name="reject_booking"
                                                class="bk-send-reject">Reject</button>
                                        </form>
                                    </div>
                                </div>
                            </article>

                        <?php endwhile; ?>

                    </section>

                </div>
            </section>

        </main>
    </div>
    <div id="modal-accept" class="md">
        <a href="#" class="md__overlay" onclick="closeAcceptModal()"></a>

        <section class="md__card" role="dialog" aria-modal="true">
            <button type="button" class="md__close" onclick="closeAcceptModal()">✕</button>

            <h2 class="md__title">Accept Order</h2>
            <p class="md__text">
                Are you sure you want to accept this order?<br>
                The customer will proceed to payment.
            </p>

            <form method="POST">
                <input type="hidden" id="accept_booking_code" name="booking_code">

                <div class="md__actions">
                    <button type="button" class="md-btn md-btn--ghost" onclick="closeAcceptModal()">Cancel</button>
                    <button type="submit" name="accept_booking" class="md-btn md-btn--primary">
                        Accept Order
                    </button>
                </div>
            </form>
        </section>
    </div>
    <div id="modal-success" class="md">
        <a href="#" class="md__overlay" onclick="closeSuccessModal()"></a>

        <section class="md__card" role="dialog" aria-modal="true">
            <button type="button" class="md__close" onclick="closeSuccessModal()">✕</button>

            <h2 class="md__title" id="success-title"></h2>

            <p class="md__text" id="success-text"></p>

            <div class="md__actions">
                <a href="#" id="success-link" class="md-btn md-btn--primary">
                    View
                </a>
            </div>
        </section>
    </div>


    <script src="/PROGNET/assets/admin/js/sidebarAdmin.js"></script>
    <script src="/PROGNET/assets/admin/js/navbarAdmin.js"></script>
    <script src="/PROGNET/assets/admin/js/bookings.js"></script>
    <script>
        document.querySelectorAll(".bk-reject-btn").forEach(btn => {
            btn.addEventListener("click", function () {

                const card = this.closest(".bk-card");
                const detailBox = card.querySelector(".bk-detail-box");
                const rejectBox = card.querySelector(".bk-reject-box");

                // tutup detail
                if (detailBox) detailBox.style.display = "none";

                // toggle reject
                if (rejectBox) {
                    rejectBox.style.display =
                        rejectBox.style.display === "block" ? "none" : "block";
                }
            });
        });
    </script>
    <script>
        function openAcceptModal(bookingCode) {
            document.getElementById('accept_booking_code').value = bookingCode;
            document.getElementById('modal-accept').classList.add('show');
        }

        function closeAcceptModal() {
            document.getElementById('modal-accept').classList.remove('show');
        }
    </script>
    <script>
        (function () {
            const params = new URLSearchParams(window.location.search);
            const success = params.get('success');

            if (!success) return;

            const modal = document.getElementById('modal-success');
            const title = document.getElementById('success-title');
            const text = document.getElementById('success-text');
            const link = document.getElementById('success-link');

            if (success === 'accepted') {
                title.textContent = 'Order Accepted';
                text.innerHTML = `
            The order has been successfully accepted.<br>
            Customer is now ready to purchase.
        `;
                link.textContent = 'View Waiting Payment';
                link.href = '/PROGNET/admin/orders/pending-payment.php';
            }

            if (success === 'rejected') {
                title.textContent = 'Order Rejected';
                text.innerHTML = `
            The order has been successfully rejected.<br>
            You can check it in the rejected orders page.
        `;
                link.textContent = 'View Order Rejected';
                link.href = '/PROGNET/admin/orders/reject.php';
            }

            modal.classList.add('show');

            // bersihkan URL biar tidak muncul lagi saat refresh
            window.history.replaceState({}, document.title, window.location.pathname);
        })();

        function closeSuccessModal() {
            document.getElementById('modal-success').classList.remove('show');
        }
    </script>

</body>

</html>