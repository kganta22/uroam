<?php
// Get top products by number of bookings
$topProductsQuery = "
    SELECT p.id, p.title, COUNT(b.booking_code) as booking_count
    FROM products p
    LEFT JOIN bookings b ON p.id = b.product_id
    GROUP BY p.id, p.title
    ORDER BY booking_count DESC
    LIMIT 5
";
$topProductsStmt = $GLOBALS['conn']->prepare($topProductsQuery);
$topProductsStmt->execute();
$topProductsResult = $topProductsStmt->get_result();
$topProducts = [];
while ($product = $topProductsResult->fetch_assoc()) {
  $topProducts[] = $product;
}
$topProductsStmt->close();

// Get company profile info
$companyQuery = "SELECT customer_service_phone, customer_service_email FROM company_profile LIMIT 1";
$companyStmt = $GLOBALS['conn']->prepare($companyQuery);
$companyStmt->execute();
$companyResult = $companyStmt->get_result();
$company = $companyResult->fetch_assoc();
$companyStmt->close();

$phone = $company['customer_service_phone'] ?? '+621234567890';
$email = $company['customer_service_email'] ?? 'uRoam@uroam.com';
// Clean phone number for WhatsApp (remove special characters except +)
$whatsappPhone = preg_replace('/[^0-9+]/', '', $phone);
?>

<footer class="footer">
  <div class="footer-inner">

    <!-- COLUMN 1 — TOP PRODUCTS -->
    <div class="footer-col">
      <h3>Top Products</h3>
      <div class="footer-line"></div>

      <?php if (!empty($topProducts)): ?>
        <?php foreach ($topProducts as $product): ?>
          <a href="/PROGNET/customer/tour-detail.php?id=<?= $product['id'] ?>" class="footer-link">
            <?= htmlspecialchars($product['title']) ?>
          </a>
        <?php endforeach; ?>
      <?php else: ?>
        <p class="footer-link" style="color: #666; pointer-events: none;">No products available</p>
      <?php endif; ?>
    </div>

    <!-- COLUMN 2 — INFORMATION -->
    <div class="footer-col">
      <h3>Information</h3>
      <div class="footer-line"></div>

      <a href="/PROGNET/customer/about-us.php" class="footer-link">About Us</a>
      <a href="/PROGNET/customer/policy.php" class="footer-link">Policy</a>
      <a href="/PROGNET/customer/terms.php" class="footer-link">Terms</a>
    </div>

    <!-- COLUMN 3 — ADDRESS -->
    <div class="footer-col">
      <h3>Address</h3>
      <div class="footer-line"></div>

      <p>PT. uRoam Bali Tours Indonesia</p>
      <p>uRoam Building</p>
      <p>Jl. Universitas Udayana No. 99</p>
      <p>Kuta Selatan, Jimbaran</p>
      <p>Indonesia, 80361</p>

      <p>For assistance, please call uRoam</p>

      <a href="tel:<?= htmlspecialchars($phone) ?>" class="footer-contact">
        <span class="footer-icon-circle"><img src="/PROGNET/images/icons/ic-telephone.svg" alt=""></span>
        <?= htmlspecialchars($phone) ?>
      </a>

      <a href="mailto:<?= htmlspecialchars($email) ?>" class="footer-contact">
        <span class="footer-icon-circle"><img src="/PROGNET/images/icons/ic-email.svg" alt=""></span>
        <?= htmlspecialchars($email) ?>
      </a>

      <a href="https://wa.me/<?= htmlspecialchars($whatsappPhone) ?>" class="footer-contact" target="_blank">
        <span class="footer-icon-circle"><img src="/PROGNET/images/icons/ic-whatsapp.svg" alt=""></span>
        WhatsApp uRoam
      </a>
    </div>

    <!-- COLUMN 4 — SOCIAL -->
    <div class="footer-col">
      <h3>Social</h3>
      <div class="footer-line"></div>

      <a href="#" class="footer-social">
        <span class="footer-icon-circle"><img src="/PROGNET/images/icons/ic-instagram.svg" alt=""></span>
        Instagram
      </a>

      <a href="#" class="footer-social">
        <span class="footer-icon-circle"><img src="/PROGNET/images/icons/ic-tiktok.svg" alt=""></span>
        TikTok
      </a>

      <a href="#" class="footer-social">
        <span class="footer-icon-circle"><img src="/PROGNET/images/icons/ic_linkedin.svg" alt=""></span>
        Linkedin
      </a>

      <a href="#" class="footer-social">
        <span class="footer-icon-circle"><img src="/PROGNET/images/icons/ic_facebook.svg" alt=""></span>
        Facebook
      </a>
    </div>

  </div>

  <div class="footer-bottom footer-container">
    <div class="footer-divider"></div>
    <p class="footer-copy">Copyright © 2025 uRoam Bali Tours Indonesia. All rights reserved.</p>
  </div>
</footer>