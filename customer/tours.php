<?php
require_once __DIR__ . '/../database/connect.php';

// Fetch categories
$categoriesQuery = "SELECT id, name FROM categories ORDER BY name ASC";
$categoriesResult = $conn->query($categoriesQuery);
$categories = [];
if ($categoriesResult) {
	while ($row = $categoriesResult->fetch_assoc()) {
		$categories[] = $row;
	}
}

// Fetch top products
$productsQuery = "
    SELECT 
        p.id,
        p.title,
        p.thumbnail,
        p.duration_hours,
        MIN(pp.child_price) as child_price,
        COUNT(b.booking_code) as total_bookings
    FROM products p
    LEFT JOIN bookings b ON p.id = b.product_id
    LEFT JOIN product_prices pp ON p.id = pp.product_id
    WHERE p.is_active = 1
    GROUP BY p.id
    ORDER BY total_bookings DESC
    LIMIT 12
";
$productsResult = $conn->query($productsQuery);
$products = [];
if ($productsResult) {
	while ($row = $productsResult->fetch_assoc()) {
		$products[] = $row;
	}
}

function formatDuration($hours)
{
	if ($hours >= 24) {
		$days = ceil($hours / 24);
		return $days . ' day' . ($days > 1 ? 's' : '');
	}
	return $hours . ' hours';
}

function formatPrice($price)
{
	if (!$price)
		return 'Contact for price';
	return 'Rp ' . number_format($price, 0, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Tours - uRoam</title>

	<link rel="icon" type="image/uroam-icon" href="/PROGNET/images/uroam-title.ico">

	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Solway:wght@300;400;500;700;800&display=swap" rel="stylesheet">

	<link rel="stylesheet" href="/PROGNET/assets/shared/css/style.css">
	<link rel="stylesheet" href="/PROGNET/assets/admin/css/sidebarAdmin.css">
	<link rel="stylesheet" href="/PROGNET/assets/customer/css/navbar.css">
	<link rel="stylesheet" href="/PROGNET/assets/customer/css/footer.css">
	<link rel="stylesheet" href="/PROGNET/assets/customer/css/tours.css">
</head>

<body>
	<!-- Sidebar -->
	<?php require_once __DIR__ . '/_partials/sidebarCustomer.html'; ?>

	<!-- Navbar -->
	<?php require_once __DIR__ . '/_partials/navbar.php'; ?>

	<div class="content-wrapper">
		<main class="tours-container">

			<!-- Search + Category Tabs -->
			<section class="t-search-row">
				<div class="t-search">
					<input type="text" id="searchInput" placeholder="Search tour" aria-label="Search tour" />
					<img src="/PROGNET/images/icons/search.svg" class="t-search-icon" alt="">
				</div>

				<div class="t-tabs-wrapper">
					<button class="t-tabs-prev" aria-label="Previous categories" style="display: none;">
						<img src="/PROGNET/images/icons/dropdown.svg" class="t-tabs-icon" alt="">
					</button>
					<div class="t-tabs" id="categoriesTabs" role="tablist" aria-label="Tour categories">
						<button class="t-tab active" data-category="top-products">Top products</button>
						<?php foreach ($categories as $category): ?>
							<button class="t-tab" data-category="<?= htmlspecialchars($category['id']) ?>">
								<?= htmlspecialchars($category['name']) ?>
							</button>
						<?php endforeach; ?>
						<div class="t-tabs-fade"></div>
					</div>
					<button class="t-tabs-next" aria-label="Next categories">
						<img src="/PROGNET/images/icons/dropdown.svg" class="t-tabs-icon" alt="">
					</button>
				</div>
			</section>

			<!-- Cards Grid -->
			<section class="t-grid" id="productsGrid">
				<?php if (empty($products)): ?>
					<p style="grid-column: 1/-1; text-align: center; color: #6b7280; padding: 40px 20px;">No products found
					</p>
				<?php else: ?>
					<?php foreach ($products as $product):
						$imageUrl = $product['thumbnail'] ?: '/PROGNET/images/placeholder.png';
						$duration = $product['duration_hours'] ? formatDuration($product['duration_hours']) : 'N/A';
						$price = formatPrice($product['child_price']);
						?>
						<article class="t-card">
							<a href="/PROGNET/customer/tour-detail.php?id=<?= htmlspecialchars($product['id']) ?>"
								class="t-card-link" aria-label="<?= htmlspecialchars($product['title']) ?>">
								<div class="t-media" style="background-image:url('<?= htmlspecialchars($imageUrl) ?>');"></div>
								<div class="t-overlay">
									<h3 class="t-title"><?= htmlspecialchars($product['title']) ?></h3>
									<div class="t-meta">
										<div class="t-meta-row">
											<span class="t-meta-item">
												<img src="/PROGNET/images/icons/clock.svg" class="t-ico" alt="">
												<span class="t-text"><?= htmlspecialchars($duration) ?></span>
											</span>
										</div>
									</div>
									<div class="t-price">
										<span class="t-price-label">Start from</span>
										<span class="t-price-value"><?= htmlspecialchars($price) ?></span>
									</div>
								</div>
							</a>
						</article>
					<?php endforeach; ?>
				<?php endif; ?>
			</section>

		</main>

	</div>
	<!-- Footer -->
	<?php require_once __DIR__ . '/_partials/footer.php'; ?>

	<script src="/PROGNET/assets/admin/js/sidebarAdmin.js"></script>
	<script src="/PROGNET/assets/customer/js/tours.js"></script>
</body>
<html>