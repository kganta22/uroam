<?php
require_once __DIR__ . '/../init.php';

$products = [];
$res = $conn->query("SELECT id, title FROM products ORDER BY title ASC");
while ($row = $res->fetch_assoc()) {
  $products[] = $row;
}

$where = [];
$params = [];
$types = "";

if (!empty($_GET['products'])) {
  $ids = $_GET['products']; // array
  $placeholders = implode(",", array_fill(0, count($ids), "?"));

  $where[] = "r.product_id IN ($placeholders)";
  foreach ($ids as $id) {
    $params[] = $id;
    $types .= "i";
  }
}

if (!empty($_GET['rating'])) {
  $ratings = $_GET['rating']; // array
  $placeholders = implode(",", array_fill(0, count($ratings), "?"));

  $where[] = "r.rating IN ($placeholders)";
  foreach ($ratings as $r) {
    $params[] = (int) $r;
    $types .= "i";
  }
}

$whereSQL = "";
if (!empty($where)) {
  $whereSQL = "WHERE " . implode(" AND ", $where);
}

$query = "
    SELECT
        r.id,
        r.booking_code,
        r.product_id,

        r.customer_name,
        r.customer_country,
        r.customer_avatar,

        r.rating,
        r.review_message,
        r.created_at,

        p.title AS product_title,

        b.activity_date
    FROM product_reviews r
    JOIN products p ON p.id = r.product_id
    LEFT JOIN bookings b ON b.booking_code = r.booking_code
    $whereSQL
    ORDER BY r.created_at DESC
";

$stmt = $conn->prepare($query);

if (!empty($params)) {
  $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$reviews = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <title>Reviews</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />

  <link rel="icon" type="image/uroam-icon" href="/PROGNET/images/uroam-title.ico">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Solway:wght@300;400;500;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/PROGNET/assets/shared/css/style.css">
  <link rel="stylesheet" href="/PROGNET/assets/shared/css/filter-product.css">
  <link rel="stylesheet" href="/PROGNET/assets/admin/css/sidebarAdmin.css">
  <link rel="stylesheet" href="/PROGNET/assets/admin/css/navbarAdmin.css">
  <link rel="stylesheet" href="/PROGNET/assets/admin/css/reviews.css" />
</head>

<body>
  <?php require_once __DIR__ . '/../_partials/sidebarAdmin.html'; ?>
  <?php require_once __DIR__ . '/../_partials/navbarAdmin.html'; ?>

  <div id="content-wrapper">
    <main class="hm-main">

      <section class="rv-hero">
        <h1 class="rv-title">Customer Reviews</h1>

        <div class="rv-layout">

          <!-- REVIEWS LIST -->
          <section class="rv-list">
            <?php if ($reviews->num_rows === 0): ?>
              <div class="rv-empty">
                No reviews found for selected filters.
              </div>
            <?php else: ?>

              <?php while ($r = $reviews->fetch_assoc()): ?>
                <article class="review-card">

                  <div class="review-header">
                    <div class="stars">
                      <?php
                      $rating = (int) $r['rating'];
                      echo str_repeat('★', $rating);
                      echo str_repeat('☆', 5 - $rating);
                      ?>
                    </div>

                    <div class="reviewer">
                      <img src="<?= !empty($r['customer_avatar'])
                        ? htmlspecialchars($r['customer_avatar'])
                        : '/PROGNET/images/icons/no-profile.png' ?>" alt="<?= htmlspecialchars($r['customer_name']) ?>"
                        class="avatar-img">
                      <div class="reviewer-info">
                        <strong><?= htmlspecialchars($r['customer_name']) ?></strong>
                        <div class="reviewer-country"><?= $r['customer_country'] ?: '-' ?></div>
                      </div>
                    </div>

                    <span class="date">
                      Posted on <?= date('M j, Y', strtotime($r['created_at'])) ?>
                    </span>
                  </div>

                  <p class="review-text">
                    <?= nl2br(htmlspecialchars($r['review_message'])) ?>
                  </p>

                  <div class="review-product">
                    <?= htmlspecialchars($r['product_title']) ?>
                  </div>

                  <div class="rv-actions">
                    <button class="rv-detail-btn">Show details</button>
                  </div>

                  <div class="rv-detail-box">
                    <div class="rv-detail-row">
                      <span class="rv-label">Booking ID</span>
                      <span class="rv-value"><?= $r['booking_code'] ?></span>
                    </div>

                    <div class="rv-detail-row">
                      <span class="rv-label">Activity date</span>
                      <span class="rv-value">
                        <?= $r['activity_date'] ? date('d-m-Y (H:i)', strtotime($r['activity_date'])) : '-' ?>
                      </span>
                    </div>
                  </div>


                </article>
              <?php endwhile; ?>
            <?php endif; ?>
          </section>

          <!-- FILTER PANEL -->
          <aside class="rv-filters">
            <form id="filterForm" method="GET">

              <label class="rv-filter-title">Filters</label>

              <!-- PRODUCT FILTER -->
              <div class="rv-filter-block">
                <?php require_once __DIR__ . '/../../assets/components/filter-product.php'; ?>
              </div>

              <!-- RATING FILTER -->
              <div class="rv-filter-block">
                <label class="rv-filter-label">Rating</label>

                <div class="rating-filters">
                  <?php for ($i = 5; $i >= 1; $i--): ?>
                    <label class="chip <?= (isset($_GET['rating']) && in_array($i, $_GET['rating'])) ? 'active' : '' ?>">
                      <input type="checkbox" name="rating[]" value="<?= $i ?>" <?= (isset($_GET['rating']) && in_array($i, $_GET['rating'])) ? 'checked' : '' ?>>
                      <?= $i ?> star<?= $i > 1 ? 's' : '' ?>
                    </label>
                  <?php endfor; ?>
                </div>
              </div>

            </form>
          </aside>
        </div>
      </section>

    </main>
  </div>

  <script src="/PROGNET/assets/shared/js/filter-product.js"></script>
  <script src="/PROGNET/assets/admin/js/sidebarAdmin.js"></script>
  <script src="/PROGNET/assets/admin/js/navbarAdmin.js"></script>
  <script src="/PROGNET/assets/admin/js/reviews.js"></script>
</body>

</html>