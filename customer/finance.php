<?php
session_start();
require_once __DIR__ . '/../database/connect.php';
require_once __DIR__ . '/_guards/customerGuard.php';

// ====== GET CURRENT CUSTOMER ID ======
$customer_id = $_SESSION['customer_id'] ?? null;

// ====== DATE RANGE FILTER ======
$startDate = $_GET['start_date'] ?? null;
$endDate = $_GET['end_date'] ?? null;

// ====== CALCULATE TOTAL BALANCE ======
if ($startDate && $endDate) {
    $balanceQuery = $conn->prepare("
        SELECT COALESCE(SUM(net_rate), 0) AS total_balance
        FROM bookings
        WHERE customer_id = ? AND purchase_date BETWEEN ? AND ?
    ");
    $balanceQuery->bind_param('iss', $customer_id, $startDate, $endDate);
} elseif ($startDate) {
    $balanceQuery = $conn->prepare("
        SELECT COALESCE(SUM(net_rate), 0) AS total_balance
        FROM bookings
        WHERE customer_id = ? AND purchase_date >= ?
    ");
    $balanceQuery->bind_param('is', $customer_id, $startDate);
} elseif ($endDate) {
    $balanceQuery = $conn->prepare("
        SELECT COALESCE(SUM(net_rate), 0) AS total_balance
        FROM bookings
        WHERE customer_id = ? AND purchase_date <= ?
    ");
    $balanceQuery->bind_param('is', $customer_id, $endDate);
} else {
    $balanceQuery = $conn->prepare("
        SELECT COALESCE(SUM(net_rate), 0) AS total_balance
        FROM bookings
        WHERE customer_id = ?
    ");
    $balanceQuery->bind_param('i', $customer_id);
}

$balanceQuery->execute();
$balanceResult = $balanceQuery->get_result()->fetch_assoc();
$totalBalance = $balanceResult['total_balance'];
$balanceQuery->close();

// ====== FETCH BOOKINGS WITH INVOICES ======
if ($startDate && $endDate) {
    $bookingsQuery = $conn->prepare("
        SELECT 
            b.activity_date,
            b.booking_code,
            b.option_name AS option,
            b.purchase_date,
            b.net_rate,
            i.invoice_path,
            i.status AS invoice_status
        FROM bookings b
        LEFT JOIN invoices i ON i.booking_code = b.booking_code
        WHERE b.customer_id = ? AND b.purchase_date BETWEEN ? AND ?
        ORDER BY b.purchase_date DESC
    ");
    $bookingsQuery->bind_param('iss', $customer_id, $startDate, $endDate);
} elseif ($startDate) {
    $bookingsQuery = $conn->prepare("
        SELECT 
            b.activity_date,
            b.booking_code,
            b.option_name AS option,
            b.purchase_date,
            b.net_rate,
            i.invoice_path,
            i.status AS invoice_status
        FROM bookings b
        LEFT JOIN invoices i ON i.booking_code = b.booking_code
        WHERE b.customer_id = ? AND b.purchase_date >= ?
        ORDER BY b.purchase_date DESC
    ");
    $bookingsQuery->bind_param('is', $customer_id, $startDate);
} elseif ($endDate) {
    $bookingsQuery = $conn->prepare("
        SELECT 
            b.activity_date,
            b.booking_code,
            b.option_name AS option,
            b.purchase_date,
            b.net_rate,
            i.invoice_path,
            i.status AS invoice_status
        FROM bookings b
        LEFT JOIN invoices i ON i.booking_code = b.booking_code
        WHERE b.customer_id = ? AND b.purchase_date <= ?
        ORDER BY b.purchase_date DESC
    ");
    $bookingsQuery->bind_param('is', $customer_id, $endDate);
} else {
    $bookingsQuery = $conn->prepare("
        SELECT 
            b.activity_date,
            b.booking_code,
            b.option_name AS option,
            b.purchase_date,
            b.net_rate,
            i.invoice_path,
            i.status AS invoice_status
        FROM bookings b
        LEFT JOIN invoices i ON i.booking_code = b.booking_code
        WHERE b.customer_id = ?
        ORDER BY b.purchase_date DESC
    ");
    $bookingsQuery->bind_param('i', $customer_id);
}

$bookingsQuery->execute();
$bookings = $bookingsQuery->get_result()->fetch_all(MYSQLI_ASSOC);
$bookingsQuery->close();

// ====== FORMAT CURRENCY ======
function formatIDR($amount)
{
    return 'IDR ' . number_format($amount, 2, '.', ',');
}

// ====== FORMAT DATE ======
function formatDate($date)
{
    return date('M j, Y', strtotime($date));
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Finance — uRoam</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Solway:wght@300;400;500;700;800&display=swap" rel="stylesheet">

    <link rel="icon" type="image/uroam-icon" href="/PROGNET/images/uroam-title.ico">

    <!-- Flatpickr for date picker -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    <!-- Global Layout -->
    <link rel="stylesheet" href="/PROGNET/assets/shared/css/style.css">
    <link rel="stylesheet" href="/PROGNET/assets/admin/css/sidebarAdmin.css">
    <link rel="stylesheet" href="/PROGNET/assets/customer/css/navbar.css">
    <link rel="stylesheet" href="/PROGNET/assets/admin/css/bookings.css">

    <!-- Page CSS -->
    <link rel="stylesheet" href="/PROGNET/assets/admin/css/finance.css">
    <link rel="stylesheet" href="/PROGNET/assets/customer/css/finance.css">
</head>

<body class="hm">
    <!-- Sidebar -->
    <?php require_once __DIR__ . '/_partials/sidebarCustomer.html'; ?>

    <!-- Navbar -->
    <?php require_once __DIR__ . '/_partials/navbar.php'; ?>

    <div class="content-wrapper">

        <main class="cp-main container">

            <h1 class="cp-title">Finance</h1>

            <section class="finance-card">

                <div class="summary">
                    <div class="summary-item">
                        <span class="label">Total Spending (IDR)</span>
                        <span class="value"><?= formatIDR($totalBalance) ?></span>
                    </div>

                    <form method="GET" id="financeFilterForm" class="filter-form">
                        <div class="bk-filter-block">
                            <div class="bk-filter-header">
                                <label class="bk-label bk-filter-label">Purchase Date Range</label>
                                <a class="bk-clear" onclick="clearFinanceDate()"
                                    style="display: <?= (!empty($_GET['start_date']) || !empty($_GET['end_date'])) ? 'inline' : 'none' ?>;">
                                    Clear
                                </a>
                            </div>

                            <div class="bk-date-input">
                                <input type="text" id="dateRange" class="bk-input bk-filter-input"
                                    placeholder="From - To" readonly>
                                <img src="/PROGNET/images/icons/calendar.svg" class="bk-calendar-icon" alt="calendar">
                            </div>

                            <!-- Hidden inputs for PHP -->
                            <input type="hidden" name="start_date" id="date_start"
                                value="<?= $_GET['start_date'] ?? '' ?>">
                            <input type="hidden" name="end_date" id="date_end" value="<?= $_GET['end_date'] ?? '' ?>">
                        </div>
                    </form>
                </div>

                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Activity date</th>
                                <th>ID Booking</th>
                                <th>Option</th>
                                <th>Purchase date</th>
                                <th>Net rate</th>
                                <th>Invoice</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($bookings)): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 32px; color: #6b7280;">
                                        No bookings found
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($bookings as $booking): ?>
                                    <tr>
                                        <td><?= formatDate($booking['activity_date']) ?></td>
                                        <td><?= htmlspecialchars($booking['booking_code']) ?></td>
                                        <td><?= ucfirst(htmlspecialchars($booking['option'])) ?></td>
                                        <td><?= formatDate($booking['purchase_date']) ?></td>
                                        <td class="net-rate"><?= formatIDR($booking['net_rate']) ?></td>
                                        <td>
                                            <button class="invoice-btn"
                                                onclick="viewInvoice(event, '<?= htmlspecialchars($booking['booking_code']) ?>', '<?= htmlspecialchars($booking['invoice_path'] ?? '') ?>')">
                                                <span>VIEW</span>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </section>

        </main>
    </div>

    <script src="/PROGNET/assets/admin/js/sidebarAdmin.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="/PROGNET/assets/admin/js/finance.js"></script>
</body>

</html>