<?php
require_once __DIR__ . '/../database/connect.php';

$tour_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$tour = null;
$gallery = [];

if ($tour_id > 0) {
    $query = "
        SELECT 
            p.id, p.title, p.full_description, p.duration_hours,
            p.include, p.exclude, p.thumbnail, p.itinerary_file
        FROM products p
        WHERE p.id = ? AND p.is_active = 1
        LIMIT 1
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $tour_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $tour = $result->fetch_assoc();
    $stmt->close();

    if ($tour) {
        // Get all gallery images (including what would be thumbnail)
        $galleryQuery = "
            SELECT photo_path FROM product_photos 
            WHERE product_id = ?
            ORDER BY id ASC
        ";
        $galleryStmt = $conn->prepare($galleryQuery);
        $galleryStmt->bind_param('i', $tour_id);
        $galleryStmt->execute();
        $galleryResult = $galleryStmt->get_result();
        while ($img = $galleryResult->fetch_assoc()) {
            $gallery[] = $img['photo_path'];
        }
        $galleryStmt->close();

        // If no gallery images, use thumbnail as fallback
        if (empty($gallery)) {
            if ($tour['thumbnail']) {
                $gallery[] = $tour['thumbnail'];
            } else {
                $gallery[] = '/PROGNET/images/no-photo.png';
            }
        }
    }
}

if (!$tour) {
    header('Location: /PROGNET/customer/tours.php');
    exit();
}

$duration_display = $tour['duration_hours'] >= 24
    ? ceil($tour['duration_hours'] / 24) . ' day' . (ceil($tour['duration_hours'] / 24) > 1 ? 's' : '')
    : $tour['duration_hours'] . ' hours';

// Get price options from product_prices and compute the lowest price as "Start from"
$priceOptions = [];
$start_price = null;

$priceQuery = "
    SELECT category, adult_price, child_price
    FROM product_prices
    WHERE product_id = ?
";
$priceStmt = $conn->prepare($priceQuery);
$priceStmt->bind_param('i', $tour_id);
$priceStmt->execute();
$priceResult = $priceStmt->get_result();
while ($row = $priceResult->fetch_assoc()) {
    $priceOptions[] = $row;

    // Determine minimal available price (adult or child) per row
    $candidatePrices = [];
    if (!is_null($row['adult_price'])) {
        $candidatePrices[] = (float) $row['adult_price'];
    }
    if (!is_null($row['child_price'])) {
        $candidatePrices[] = (float) $row['child_price'];
    }
    if ($candidatePrices) {
        $rowMin = min($candidatePrices);
        if (is_null($start_price) || $rowMin < $start_price) {
            $start_price = $rowMin;
        }
    }
}
$priceStmt->close();

$price_display = $start_price ? 'Rp ' . number_format($start_price, 0, ',', '.') . '/pax' : 'Contact for price';

// Get customer reviews for this product
$reviews = [];
if ($tour) {
    $reviewQuery = "
        SELECT 
            pr.customer_name, pr.customer_country, pr.customer_avatar,
            pr.rating, pr.review_message, pr.created_at,
            b.activity_date
        FROM product_reviews pr
        JOIN bookings b ON b.booking_code = pr.booking_code
        WHERE pr.product_id = ? AND pr.is_published = 1
        ORDER BY pr.created_at DESC
    ";
    $reviewStmt = $conn->prepare($reviewQuery);
    $reviewStmt->bind_param('i', $tour_id);
    $reviewStmt->execute();
    $reviewResult = $reviewStmt->get_result();
    while ($review = $reviewResult->fetch_assoc()) {
        $reviews[] = $review;
    }
    $reviewStmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($tour['title']); ?> - uRoam</title>

    <link rel="icon" type="image/uroam-icon" href="/PROGNET/images/uroam-title.ico">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Solway:wght@300;400;500;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="/PROGNET/assets/shared/css/style.css">
    <link rel="stylesheet" href="/PROGNET/assets/admin/css/sidebarAdmin.css">
    <link rel="stylesheet" href="/PROGNET/assets/customer/css/navbar.css">
    <link rel="stylesheet" href="/PROGNET/assets/customer/css/footer.css">
    <link rel="stylesheet" href="/PROGNET/assets/customer/css/tour-detail.css">
</head>

<body>
    <!-- Sidebar -->
    <?php require_once __DIR__ . '/_partials/sidebarCustomer.html'; ?>

    <!-- Navbar -->
    <?php require_once __DIR__ . '/_partials/navbar.php'; ?>

    <div class="content-wrapper">
        <main class="tour-detail-container">
            <!-- Gallery Section -->
            <section class="gallery-section">
                <div class="gallery-main">
                    <img id="mainImage"
                        src="<?php echo htmlspecialchars($gallery[0] ?? '/PROGNET/images/no-photo.png'); ?>"
                        alt="<?php echo htmlspecialchars($tour['title']); ?>" class="main-image">

                    <button class="gallery-nav gallery-prev" aria-label="Previous image" id="galleryPrev">
                        <img src="/PROGNET/images/icons/chevron-left.svg" alt="">
                    </button>
                    <button class="gallery-nav gallery-next" aria-label="Next image" id="galleryNext">
                        <img src="/PROGNET/images/icons/chevron-right.svg" alt="">
                    </button>
                </div>

                <!-- Thumbnails -->
                <div class="gallery-thumbnails" id="galleryThumbnails">
                    <?php foreach ($gallery as $index => $img): ?>
                        <button class="thumbnail <?php echo $index === 0 ? 'active' : ''; ?>"
                            data-index="<?php echo $index; ?>" aria-label="View gallery image">
                            <img src="<?php echo htmlspecialchars($img); ?>" alt="">
                        </button>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- Info Section -->
            <section class="info-section">
                <h1 class="tour-title"><?php echo htmlspecialchars($tour['title']); ?></h1>

                <!-- Meta Information -->
                <div class="meta-info">
                    <div class="meta-item">
                        <img src="/PROGNET/images/icons/clock.svg" alt="Duration" class="meta-icon">
                        <div class="meta-content">
                            <div class="meta-label">Duration</div>
                            <div class="meta-value"><?php echo htmlspecialchars($duration_display); ?></div>
                        </div>
                    </div>

                    <div class="meta-item price-block">
                        <img src="/PROGNET/images/icons/price.svg" alt="Price" class="meta-icon">
                        <div class="meta-content price-content">
                            <div class="price-header">
                                <div>
                                    <div class="meta-label">Start from</div>
                                    <div class="meta-value"><?php echo htmlspecialchars($price_display); ?></div>
                                </div>
                                <?php if (!empty($priceOptions)): ?>
                                    <button class="price-toggle" type="button" aria-expanded="false">
                                        <span>View options</span>
                                        <img src="/PROGNET/images/icons/chevron-down.svg" alt="" class="price-toggle-icon">
                                    </button>
                                <?php endif; ?>
                            </div>

                            <?php if (!empty($priceOptions)): ?>
                                <div class="price-dropdown" hidden>
                                    <?php foreach ($priceOptions as $opt): ?>
                                        <div class="price-option">
                                            <div class="option-name"><?php echo ucfirst(htmlspecialchars($opt['category'])); ?>
                                            </div>
                                            <div class="option-prices">
                                                <div class="price-row">
                                                    <span class="price-label">Adult 12+</span>
                                                    <span
                                                        class="price-value"><?php echo $opt['adult_price'] !== null ? 'Rp ' . number_format($opt['adult_price'], 0, ',', '.') : '-'; ?></span>
                                                </div>
                                                <div class="price-row">
                                                    <span class="price-label">Child ≤ 12</span>
                                                    <span
                                                        class="price-value"><?php echo $opt['child_price'] !== null ? 'Rp ' . number_format($opt['child_price'], 0, ',', '.') : '-'; ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Action Button -->
                <div class="download-wrapper">
                    <img src="/PROGNET/images/icons/download-pdf.svg" alt="" class="download-icon">
                    <button class="btn-download-itinerary"
                        data-file="<?php echo htmlspecialchars($tour['itinerary_file'] ?? ''); ?>">
                        Download Itinerary
                    </button>
                </div>
            </section>

            <!-- Details Section -->
            <section class="details-section">
                <!-- Description Accordion -->
                <div class="accordion-item">
                    <button class="accordion-header" data-section="description">
                        <span>Description</span>
                        <img src="/PROGNET/images/icons/chevron-down.svg" alt="" class="accordion-icon">
                    </button>
                    <div class="accordion-content" id="description">
                        <p><?php echo nl2br(htmlspecialchars($tour['full_description'])); ?></p>
                    </div>
                </div>

                <!-- Include Accordion -->
                <div class="accordion-item">
                    <button class="accordion-header" data-section="include">
                        <span>Include</span>
                        <img src="/PROGNET/images/icons/chevron-down.svg" alt="" class="accordion-icon">
                    </button>
                    <div class="accordion-content" id="include">
                        <p><?php echo nl2br(htmlspecialchars($tour['include'])); ?></p>
                    </div>
                </div>

                <!-- Exclude Accordion -->
                <div class="accordion-item">
                    <button class="accordion-header" data-section="exclude">
                        <span>Exclude</span>
                        <img src="/PROGNET/images/icons/chevron-down.svg" alt="" class="accordion-icon">
                    </button>
                    <div class="accordion-content" id="exclude">
                        <p><?php echo nl2br(htmlspecialchars($tour['exclude'])); ?></p>
                    </div>
                </div>
            </section>

            <!-- Customer Reviews Section -->
            <section class="reviews-section">
                <h2 class="section-title">Customer Review</h2>
                <?php if (!empty($reviews)): ?>
                    <div class="reviews-list">
                        <?php foreach ($reviews as $review): ?>
                            <article class="review-card">
                                <div class="review-header">
                                    <div class="stars">
                                        <?php
                                        $rating = (int) $review['rating'];
                                        echo str_repeat('★', $rating);
                                        echo str_repeat('☆', 5 - $rating);
                                        ?>
                                    </div>

                                    <div class="reviewer">
                                        <img src="<?php echo !empty($review['customer_avatar']) ? htmlspecialchars($review['customer_avatar']) : '/PROGNET/images/icons/no-profile.png'; ?>"
                                            alt="<?php echo htmlspecialchars($review['customer_name']); ?>" class="avatar-img">
                                        <div class="reviewer-info">
                                            <strong><?php echo htmlspecialchars($review['customer_name']); ?></strong>
                                            <span><?php echo htmlspecialchars($review['customer_country'] ?: '-'); ?></span>
                                        </div>
                                    </div>

                                    <span class="review-date">
                                        Posted on <?php echo date('M j, Y', strtotime($review['created_at'])); ?>
                                    </span>
                                </div>

                                <p class="review-text">
                                    <?php echo nl2br(htmlspecialchars($review['review_message'])); ?>
                                </p>

                                <div class="review-product">
                                    Product: <?php echo htmlspecialchars($tour['title']); ?>
                                </div>
                                <div class="review-activity-date">
                                    Activity date: <?php echo date('d-m-Y (H:i)', strtotime($review['activity_date'])); ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="reviews-empty">
                        No reviews found for this tour yet. Be the first to share your experience!
                    </div>
                <?php endif; ?>
            </section>
        </main>
    </div>

    <a class="floating-book-now" href="/PROGNET/customer/book.php?id=<?php echo $tour_id; ?>"
        aria-label="Book this tour">
        <img src="/PROGNET/images/icons/book-now.svg" alt="" class="book-now-icon">
        <span>Book now</span>
    </a>

    <!-- Footer -->
    <?php require_once __DIR__ . '/_partials/footer.php'; ?>

    <script src="/PROGNET/assets/admin/js/sidebarAdmin.js"></script>
    <script src="/PROGNET/assets/customer/js/tour-detail.js"></script>
</body>

</html>