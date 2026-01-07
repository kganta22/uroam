<?php
require_once __DIR__ . '/../_guards/customerGuard.php';
require_once __DIR__ . '/../../database/connect.php';

// Get booking code from URL
$booking_code = $_GET['booking_code'] ?? null;
$customer_id = $_SESSION['customer_id'] ?? null;

if (!$customer_id || !$booking_code) {
    header('Location: /PROGNET/customer/orders/reviews.php');
    exit();
}

// Get booking details from finalized bookings table
$stmt = $conn->prepare("
    SELECT b.booking_code, b.product_id, b.customer_name, b.activity_date, b.meeting_point,
           p.title, p.thumbnail
    FROM bookings b
    JOIN products p ON p.id = b.product_id
    WHERE b.booking_code = ? AND b.customer_id = ?
");
$stmt->bind_param('si', $booking_code, $customer_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    header('Location: /PROGNET/customer/orders/reviews.php');
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful - uRoam</title>

    <link rel="icon" type="image/uroam-icon" href="/PROGNET/images/uroam-title.ico">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Solway:wght@300;400;500;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="/PROGNET/assets/shared/css/style.css">
    <link rel="stylesheet" href="/PROGNET/assets/admin/css/sidebarAdmin.css">
    <link rel="stylesheet" href="/PROGNET/assets/customer/css/navbar.css">
    <link rel="stylesheet" href="/PROGNET/assets/customer/css/footer.css">
    <style>
        .payment-success-container {
            margin: 96px auto 60px auto;
            padding: 20px;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: calc(100vh - 200px);
        }

        .success-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 40px 32px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            max-width: 560px;
            width: 100%;
        }

        .success-icon {
            width: 88px;
            height: 88px;
            background: #10b981;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: white;
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.25);
        }

        .success-icon svg {
            width: 44px;
            height: 44px;
        }

        .success-title {
            font-family: 'Solway', serif;
            font-size: 24px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 10px;
        }

        .success-message {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 30px;
        }

        .success-details {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
            text-align: left;
        }

        .success-detail-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .success-detail-row:last-child {
            border-bottom: none;
        }

        .success-detail-label {
            font-size: 13px;
            color: #6b7280;
        }

        .success-detail-value {
            font-size: 13px;
            color: #111827;
            font-weight: 600;
        }

        .success-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .success-button {
            flex: 1;
            padding: 12px 20px;
            border: none;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .success-button.primary {
            background: #ff8c42;
            color: white;
        }

        .success-button.primary:hover {
            background: #e67a35;
        }

        .success-button.secondary {
            background: #f3f4f6;
            color: #111827;
            border: 1px solid #e5e7eb;
        }

        .success-button.secondary:hover {
            background: #e5e7eb;
        }
    </style>
</head>

<body>
    <!-- Sidebar -->
    <?php require_once __DIR__ . '/../_partials/sidebarCustomer.html'; ?>

    <!-- Navbar -->
    <?php require_once __DIR__ . '/../_partials/navbar.php'; ?>

    <div class="content-wrapper">
        <main class="payment-success-container">
            <div class="success-card">
                <div class="success-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" role="presentation">
                        <path d="M20 6L9 17L4 12" stroke="white" stroke-width="2.5" stroke-linecap="round"
                            stroke-linejoin="round" />
                    </svg>
                </div>

                <h1 class="success-title">Payment Successful!</h1>
                <p class="success-message">Your booking payment has been confirmed. Your tour provider will review your
                    booking shortly.</p>

                <div class="success-details">
                    <div class="success-detail-row">
                        <span class="success-detail-label">Booking Code</span>
                        <span class="success-detail-value"><?= htmlspecialchars($order['booking_code']) ?></span>
                    </div>

                    <div class="success-detail-row">
                        <span class="success-detail-label">Tour</span>
                        <span class="success-detail-value"><?= htmlspecialchars($order['title']) ?></span>
                    </div>

                    <div class="success-detail-row">
                        <span class="success-detail-label">Activity Date</span>
                        <span
                            class="success-detail-value"><?= date('d-m-Y (H:i)', strtotime($order['activity_date'])) ?></span>
                    </div>

                    <div class="success-detail-row">
                        <span class="success-detail-label">Meeting Point</span>
                        <span
                            class="success-detail-value"><?= htmlspecialchars($order['meeting_point'] ?? 'N/A') ?></span>
                    </div>
                </div>

                <div class="success-buttons">
                    <a href="/PROGNET/customer/my-trip.php" class="success-button primary">View My Trips</a>
                    <a href="/PROGNET/customer/tours.php" class="success-button secondary">Browse More Tours</a>
                </div>
            </div>
        </main>
    </div>

    <!-- Footer -->
    <?php require_once __DIR__ . '/../_partials/footer.php'; ?>

    <script src="/PROGNET/assets/admin/js/sidebarAdmin.js"></script>
</body>

</html>