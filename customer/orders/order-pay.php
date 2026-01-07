<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../_guards/customerGuard.php';
require_once __DIR__ . '/../../database/connect.php';

// Load .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

// Get customer ID from session
$customer_id = $_SESSION['customer_id'] ?? null;
$booking_code = $_GET['booking_code'] ?? null;

if (!$customer_id) {
    header('Location: /PROGNET/customer/auth/login.php');
    exit();
}

if (!$booking_code) {
    header('Location: /PROGNET/customer/orders/reviews.php');
    exit();
}

// Get order details
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
        p.title AS product_title,
        p.duration_hours AS duration,
        p.thumbnail AS thumb
    FROM order_request o
    JOIN products p ON p.id = o.product_id
    WHERE o.booking_code = ? AND o.customer_id = ? AND o.status = 'payment'
";

$stmt = $conn->prepare($query);
$stmt->bind_param('si', $booking_code, $customer_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    header('Location: /PROGNET/customer/orders/reviews.php');
    exit();
}

// Get customer points
$stmt = $conn->prepare("SELECT point FROM customers WHERE id = ?");
$stmt->bind_param('i', $customer_id);
$stmt->execute();
$customer = $stmt->get_result()->fetch_assoc();
$customer_points = $customer['point'] ?? 0;

// Assuming 1 point = Rp 1
$points_to_rupiah = $customer_points * 1;

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
    <link rel="stylesheet" href="/PROGNET/assets/customer/css/custom-alert.css">
    <link rel="stylesheet" href="/PROGNET/assets/customer/css/order-pay.css">
</head>

<body>
    <!-- Sidebar -->
    <?php require_once __DIR__ . '/../_partials/sidebarCustomer.html'; ?>

    <!-- Navbar -->
    <?php require_once __DIR__ . '/../_partials/navbar.php'; ?>

    <div class="content-wrapper">
        <main class="hm-main order-pay-container">
            <section class="pay-section">
                <h1 class="pay-title">Complete Your Payment</h1>

                <div class="pay-layout">
                    <!-- LEFT: ORDER SUMMARY -->
                    <div class="pay-left">
                        <div class="pay-card order-summary-card">
                            <h2 class="pay-card-title">Order Summary</h2>

                            <div class="pay-summary-item">
                                <img class="pay-thumb" src="<?= $order['thumb'] ?: '/PROGNET/images/no-photo.png' ?>">
                                <div class="pay-summary-info">
                                    <h3 class="pay-product-title"><?= htmlspecialchars($order['product_title']) ?></h3>
                                    <p class="pay-product-option"><?= htmlspecialchars($order['option_name']) ?> Tour
                                    </p>
                                    <p class="pay-product-date">Activity Date:
                                        <?= date('d-m-Y (H:i)', strtotime($order['activity_date'])) ?></p>
                                </div>
                            </div>

                            <div class="pay-divider"></div>

                            <div class="pay-detail-row">
                                <span class="pay-label">Booking Code</span>
                                <span class="pay-value"><?= htmlspecialchars($order['booking_code']) ?></span>
                            </div>

                            <div class="pay-detail-row">
                                <span class="pay-label">Participants</span>
                                <span class="pay-value"><?= $order['total_adult'] ?> Adult(s),
                                    <?= $order['total_child'] ?> Child(ren)</span>
                            </div>

                            <div class="pay-detail-row">
                                <span class="pay-label">Duration</span>
                                <span class="pay-value"><?= $order['duration'] ?> hours</span>
                            </div>

                            <div class="pay-detail-row">
                                <span class="pay-label">Gross Amount</span>
                                <span class="pay-value">IDR
                                    <?= number_format($order['gross_rate'], 0, '.', '.') ?></span>
                            </div>
                        </div>

                        <!-- MEETING POINT CARD -->
                        <div class="pay-card meeting-point-card">
                            <h2 class="pay-card-title">Meeting Point</h2>

                            <div class="pay-form-group">
                                <label for="meeting_point" class="pay-label">Select or Enter Meeting Point *</label>
                                <div class="meeting-point-input-wrapper">
                                    <input type="text" id="meeting_point" name="meeting_point"
                                        placeholder="e.g., Hotel Ubud, Sanur Harbour, Airport"
                                        class="pay-input meeting-point-input" required>
                                </div>
                                <small class="pay-helper">Tell us where you'd like to be picked up</small>
                            </div>
                        </div>

                        <!-- POINTS CARD -->
                        <div class="pay-card points-card">
                            <h2 class="pay-card-title">Use Points</h2>

                            <div class="pay-points-info">
                                <p class="pay-points-balance">
                                    You have <strong><?= number_format($customer_points) ?></strong> points
                                    <span class="pay-points-value">(IDR
                                        <?= number_format($points_to_rupiah, 0, '.', '.') ?>)</span>
                                </p>
                            </div>

                            <div class="pay-checkbox-wrapper">
                                <input type="checkbox" id="use_points" name="use_points" class="pay-checkbox">
                                <label for="use_points" class="pay-checkbox-label">
                                    Use my points for this booking
                                </label>
                            </div>

                            <div id="points_customization" class="pay-points-customization" style="display: none;">
                                <div class="pay-points-slider-wrapper">
                                    <label for="points_slider" class="pay-label">Points to Use</label>
                                    <input type="range" id="points_slider" class="pay-points-slider" min="0" max="1"
                                        value="0" step="1">
                                </div>

                                <div class="pay-points-input-wrapper">
                                    <label for="points_input" class="pay-label">Or enter amount (points)</label>
                                    <input type="number" id="points_input" class="pay-input pay-points-input" min="0"
                                        max="1" value="0" placeholder="0">
                                </div>

                                <div id="points_deduction_info" class="pay-points-deduction">
                                    <p class="pay-points-deduction-text">
                                        Points to deduct: <span id="points_amount_text">0</span> points = IDR <span
                                            id="points_rupiah_text">0</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT: PRICE SUMMARY -->
                    <div class="pay-right">
                        <div class="pay-card price-card">
                            <h2 class="pay-card-title">Payment Summary</h2>

                            <div class="pay-price-row">
                                <span class="pay-price-label">Gross Amount</span>
                                <span class="pay-price-value" id="display_gross_rate">
                                    IDR <?= number_format($order['gross_rate'], 0, '.', '.') ?>
                                </span>
                            </div>

                            <div class="pay-price-row" id="points_discount_row" style="display: none;">
                                <span class="pay-price-label">Points Discount</span>
                                <span class="pay-price-value discount" id="display_points_discount">
                                    - IDR 0
                                </span>
                            </div>

                            <div class="pay-price-row total">
                                <span class="pay-price-label">Total Amount</span>
                                <span class="pay-price-value total-price" id="total_amount">
                                    IDR <?= number_format($order['gross_rate'], 0, '.', '.') ?>
                                </span>
                            </div>

                            <div class="pay-points-earned">
                                <p class="pay-points-earned-text">
                                    You will earn <strong
                                        id="points_earned_display"><?= number_format(floor($order['gross_rate'] * 0.13)) ?></strong>
                                    points from this purchase
                                </p>
                            </div>

                            <input type="hidden" id="final_amount" value="<?= $order['gross_rate'] ?>">
                            <input type="hidden" id="gross_rate_value" value="<?= $order['gross_rate'] ?>">
                            <input type="hidden" id="points_available" value="<?= $customer_points ?>">
                            <input type="hidden" id="points_to_rupiah" value="<?= $points_to_rupiah ?>">
                            <input type="hidden" id="booking_code_value" value="<?= htmlspecialchars($booking_code) ?>">

                            <button id="pay_btn" class="pay-button" disabled>
                                <span class="pay-button-text">Proceed to Payment</span>
                                <span class="pay-button-loading" style="display: none;">Processing...</span>
                            </button>

                            <p class="pay-secure">
                                <i class="fas fa-lock"></i>
                                Secure payment powered by Midtrans
                            </p>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <!-- Footer -->
    <?php require_once __DIR__ . '/../_partials/footer.php'; ?>

    <script src="/PROGNET/assets/admin/js/sidebarAdmin.js"></script>
    <script src="/PROGNET/assets/customer/js/custom-alert.js"></script>
    <script src="https://app.sandbox.midtrans.com/snap/snap.js"
        data-client-key="<?php echo $_ENV['MIDTRANS_CLIENT_KEY'] ?? ''; ?>"></script>
    <script src="/PROGNET/assets/customer/js/order-pay.js"></script>
</body>

</html>