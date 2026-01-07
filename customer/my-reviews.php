<?php
require_once __DIR__ . '/_guards/customerGuard.php';
require_once __DIR__ . '/../database/connect.php';

// Get customer ID from session
$customer_id = $_SESSION['customer_id'] ?? null;

if (!$customer_id) {
    header('Location: /PROGNET/customer/auth/login.php');
    exit();
}

// Get reviews created by this customer
$reviews = [];
$reviewQuery = "
    SELECT 
        pr.customer_name, pr.customer_country, pr.customer_avatar,
        pr.rating, pr.review_message, pr.created_at,
        p.title as product_title,
        b.activity_date
    FROM product_reviews pr
    JOIN products p ON pr.product_id = p.id
    JOIN bookings b ON b.booking_code = pr.booking_code
    WHERE pr.customer_id = ?
    ORDER BY pr.created_at DESC
";
$reviewStmt = $conn->prepare($reviewQuery);
$reviewStmt->bind_param('i', $customer_id);
$reviewStmt->execute();
$reviewResult = $reviewStmt->get_result();
while ($review = $reviewResult->fetch_assoc()) {
    $reviews[] = $review;
}
$reviewStmt->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Reviews - uRoam</title>

    <link rel="icon" type="image/uroam-icon" href="/PROGNET/images/uroam-title.ico">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Solway:wght@300;400;500;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="/PROGNET/assets/shared/css/style.css">
    <link rel="stylesheet" href="/PROGNET/assets/admin/css/sidebarAdmin.css">
    <link rel="stylesheet" href="/PROGNET/assets/customer/css/navbar.css">
    <link rel="stylesheet" href="/PROGNET/assets/customer/css/footer.css">
    <link rel="stylesheet" href="/PROGNET/assets/customer/css/my-reviews.css">
</head>

<body>
    <!-- Sidebar -->
    <?php require_once __DIR__ . '/_partials/sidebarCustomer.html'; ?>

    <!-- Navbar -->
    <?php require_once __DIR__ . '/_partials/navbar.php'; ?>

    <div class="content-wrapper">
        <main class="hm-main my-reviews-container">
            <section class="review-hero">
                <h1 class="review-title">My Reviews</h1>

                <?php if (!empty($reviews)): ?>
                    <section class="review-list">
                        <div class="reviews-grid">
                            <?php foreach ($reviews as $review): ?>
                                <article class="review-card">
                                    <div class="review-top">
                                        <div class="stars">
                                            <?php
                                            $rating = (int) $review['rating'];
                                            echo str_repeat('★', $rating);
                                            echo str_repeat('☆', 5 - $rating);
                                            ?>
                                        </div>

                                        <div class="reviewer-info">
                                            <img src="<?php echo !empty($review['customer_avatar']) ? htmlspecialchars($review['customer_avatar']) : '/PROGNET/images/icons/no-profile.png'; ?>"
                                                alt="<?php echo htmlspecialchars($review['customer_name']); ?>"
                                                class="reviewer-avatar">
                                            <div class="reviewer-details">
                                                <strong
                                                    class="reviewer-name"><?php echo htmlspecialchars($review['customer_name']); ?></strong>
                                                <span
                                                    class="reviewer-country"><?php echo htmlspecialchars($review['customer_country'] ?: '-'); ?></span>
                                            </div>
                                        </div>

                                        <span class="review-date">Posted on
                                            <?php echo date('M j, Y', strtotime($review['created_at'])); ?></span>
                                    </div>

                                    <p class="review-text">
                                        <?php echo nl2br(htmlspecialchars($review['review_message'])); ?>
                                    </p>

                                    <div class="review-product">
                                        Product: <?php echo htmlspecialchars($review['product_title']); ?>
                                    </div>
                                    <div class="review-activity-date">
                                        Activity date: <?php echo date('d-m-Y (H:i)', strtotime($review['activity_date'])); ?>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php else: ?>
                    <div class="review-empty">
                        <p>You haven't written any reviews yet. Share your experience!</p>
                    </div>
                <?php endif; ?>
            </section>
        </main>
    </div>

    <!-- Footer -->
    <?php require_once __DIR__ . '/_partials/footer.php'; ?>

    <script src="/PROGNET/assets/admin/js/sidebarAdmin.js"></script>
</body>

</html>