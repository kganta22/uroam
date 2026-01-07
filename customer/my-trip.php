<?php
require_once __DIR__ . '/_guards/customerGuard.php';
require_once __DIR__ . '/../database/connect.php';

// Get customer ID from session
$customer_id = $_SESSION['customer_id'] ?? null;

if (!$customer_id) {
    header('Location: /PROGNET/customer/auth/login.php');
    exit();
}

// Get bookings for this customer
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
        b.reviewed,
        b.created_at,
        p.title AS product_title,
        (SELECT photo_path 
         FROM product_photos 
         WHERE product_id = b.product_id 
         LIMIT 1) AS thumb
    FROM bookings b
    JOIN products p ON p.id = b.product_id
    WHERE b.customer_id = ?
    ORDER BY (b.activity_date > NOW()) DESC, b.activity_date ASC
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
    <title>My Trips - uRoam</title>

    <link rel="icon" type="image/uroam-icon" href="/PROGNET/images/uroam-title.ico">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Solway:wght@300;400;500;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="/PROGNET/assets/shared/css/style.css">
    <link rel="stylesheet" href="/PROGNET/assets/admin/css/sidebarAdmin.css">
    <link rel="stylesheet" href="/PROGNET/assets/customer/css/navbar.css">
    <link rel="stylesheet" href="/PROGNET/assets/customer/css/footer.css">
    <link rel="stylesheet" href="/PROGNET/assets/admin/css/bookings.css">
    <link rel="stylesheet" href="/PROGNET/assets/customer/css/my-trip.css">
    <link rel="stylesheet" href="/PROGNET/assets/customer/css/custom-alert.css">
</head>

<body>
    <!-- Sidebar -->
    <?php require_once __DIR__ . '/_partials/sidebarCustomer.html'; ?>

    <!-- Navbar -->
    <?php require_once __DIR__ . '/_partials/navbar.php'; ?>

    <div class="content-wrapper">
        <main class="hm-main my-trip-container">
            <section class="bk-hero">
                <h1 class="bk-title">My Trips</h1>

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
                                            Purchased on <?= $b['purchase_date'] ?>
                                        </span>
                                    </h3>

                                    <p class="bk-item-sub">Option: <?= htmlspecialchars($b['option_name']) ?></p>

                                    <div class="bk-meta-row">
                                        <span>Activity date: <?= date('d-m-Y (H:i)', strtotime($b['activity_date'])) ?></span>
                                        <span><?= $b['booking_code'] ?></span>
                                        <span>
                                            <?= ($b['total_adult'] + $b['total_child']) ?> people -
                                            IDR <?= number_format($b['net_rate']) ?>
                                        </span>
                                    </div>

                                    <div class="bk-actions">
                                        <button class="bk-detail-btn">Show details</button>
                                        <?php
                                        $activityTime = strtotime($b['activity_date']);
                                        $currentTime = time();
                                        if ($activityTime < $currentTime):
                                            if (!$b['reviewed']):
                                                ?>
                                                <button class="bk-review-btn"
                                                    onclick="openReviewModal('<?= htmlspecialchars($b['booking_code']) ?>')">Write
                                                    Review</button>
                                            <?php
                                            else:
                                                ?>
                                                <button class="bk-review-btn bk-review-btn--reviewed" disabled>Reviewed</button>
                                            <?php
                                            endif;
                                        endif;
                                        ?>
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
                                            <span class="bk-label">Your Email</span>
                                            <span class="bk-value"><?= htmlspecialchars($b['email']) ?></span>
                                        </div>

                                        <div class="bk-detail-row">
                                            <span class="bk-label">Your Phone</span>
                                            <span class="bk-value"><?= htmlspecialchars($b['phone']) ?></span>
                                        </div>
                                    </div>
                                </div>

                                <?php
                                $activityTime = strtotime($b['activity_date']);
                                $currentTime = time();
                                if ($activityTime < $currentTime && !$b['reviewed']):
                                    ?>
                                    <div class="review-modal" id="review-modal-<?= htmlspecialchars($b['booking_code']) ?>">
                                        <div class="review-modal-content">
                                            <button class="review-modal-close"
                                                onclick="closeReviewModal('<?= htmlspecialchars($b['booking_code']) ?>')">×</button>
                                            <h2 class="review-modal-title">Write a Review</h2>
                                            <form id="review-form-<?= htmlspecialchars($b['booking_code']) ?>"
                                                onsubmit="submitReview(event, '<?= htmlspecialchars($b['booking_code']) ?>')">
                                                <div class="review-form-group">
                                                    <label class="review-form-label">Rating</label>
                                                    <div class="review-rating-container"
                                                        id="rating-<?= htmlspecialchars($b['booking_code']) ?>">
                                                        <span class="review-star" data-rating="1"
                                                            onclick="setRating(this, '<?= htmlspecialchars($b['booking_code']) ?>')">★</span>
                                                        <span class="review-star" data-rating="2"
                                                            onclick="setRating(this, '<?= htmlspecialchars($b['booking_code']) ?>')">★</span>
                                                        <span class="review-star" data-rating="3"
                                                            onclick="setRating(this, '<?= htmlspecialchars($b['booking_code']) ?>')">★</span>
                                                        <span class="review-star" data-rating="4"
                                                            onclick="setRating(this, '<?= htmlspecialchars($b['booking_code']) ?>')">★</span>
                                                        <span class="review-star" data-rating="5"
                                                            onclick="setRating(this, '<?= htmlspecialchars($b['booking_code']) ?>')">★</span>
                                                    </div>
                                                    <input type="hidden"
                                                        id="rating-value-<?= htmlspecialchars($b['booking_code']) ?>" name="rating"
                                                        value="5">
                                                </div>

                                                <div class="review-form-group">
                                                    <label class="review-form-label"
                                                        for="message-<?= htmlspecialchars($b['booking_code']) ?>">Review
                                                        Message</label>
                                                    <textarea class="review-form-textarea"
                                                        id="message-<?= htmlspecialchars($b['booking_code']) ?>" name="message"
                                                        placeholder="Share your experience..." required></textarea>
                                                </div>

                                                <button type="submit" class="review-submit-btn">Submit Review</button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </article>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="bk-empty">
                            <p>You haven't booked any trips yet. Explore our tours!</p>
                            <a href="/PROGNET/customer/tours.php" class="bk-empty-btn">Browse Tours</a>
                        </div>
                    <?php endif; ?>
                </section>
            </section>
        </main>
    </div>

    <!-- Footer -->
    <?php require_once __DIR__ . '/_partials/footer.php'; ?>

    <script src="/PROGNET/assets/admin/js/sidebarAdmin.js"></script>
    <script src="/PROGNET/assets/admin/js/bookings.js"></script>
    <script src="/PROGNET/assets/customer/js/custom-alert.js"></script>
    <script src="/PROGNET/assets/customer/js/my-trip.js"></script>
</body>

</html>