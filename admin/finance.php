<?php
require_once __DIR__ . '/init.php';

// ====== DATE RANGE FILTER ======
$startDate = $_GET['start_date'] ?? null;
$endDate = $_GET['end_date'] ?? null;

// Build date filter conditions
$dateCondition = "1=1"; // Default: show all
$dateParams = [];
$dateTypes = "";

if ($startDate && $endDate) {
    $dateCondition = "purchase_date BETWEEN ? AND ?";
    $dateParams = [$startDate, $endDate];
    $dateTypes = "ss";
} elseif ($startDate) {
    $dateCondition = "purchase_date >= ?";
    $dateParams = [$startDate];
    $dateTypes = "s";
} elseif ($endDate) {
    $dateCondition = "purchase_date <= ?";
    $dateParams = [$endDate];
    $dateTypes = "s";
}

// ====== CALCULATE TOTAL BALANCE ======
$balanceQuery = $conn->prepare("
    SELECT COALESCE(SUM(net_rate), 0) AS total_balance
    FROM bookings
    WHERE $dateCondition
");

if (!$balanceQuery) {
    die("Balance Query Error: " . $conn->error);
}

// Bind parameters only if date filter is active
if (count($dateParams) === 1) {
    $balanceQuery->bind_param($dateTypes, $dateParams[0]);
} elseif (count($dateParams) === 2) {
    $balanceQuery->bind_param($dateTypes, $dateParams[0], $dateParams[1]);
}
$balanceQuery->execute();
$balanceResult = $balanceQuery->get_result()->fetch_assoc();
$totalBalance = $balanceResult['total_balance'];
$balanceQuery->close();

// ====== FETCH BOOKINGS WITH INVOICES ======
$bookingsQuery = $conn->prepare("
    SELECT 
        b.activity_date,
        b.booking_code,
        b.customer_name,
        p.reference_code,
        b.option_name AS option,
        b.purchase_date,
        b.net_rate,
        i.invoice_path,
        i.status AS invoice_status
    FROM bookings b
    LEFT JOIN products p ON p.id = b.product_id
    LEFT JOIN invoices i ON i.booking_code = b.booking_code
    WHERE $dateCondition
    ORDER BY b.purchase_date DESC
");

if (!$bookingsQuery) {
    die("Bookings Query Error: " . $conn->error);
}

// Bind parameters only if date filter is active
if (count($dateParams) === 1) {
    $bookingsQuery->bind_param($dateTypes, $dateParams[0]);
} elseif (count($dateParams) === 2) {
    $bookingsQuery->bind_param($dateTypes, $dateParams[0], $dateParams[1]);
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
    <link rel="stylesheet" href="/PROGNET/assets/shared/css/sidebarAdmin.css">
    <link rel="stylesheet" href="/PROGNET/assets/admin/css/sidebarAdmin.css">
    <link rel="stylesheet" href="/PROGNET/assets/admin/css/navbarAdmin.css">
    <link rel="stylesheet" href="/PROGNET/assets/admin/css/bookings.css">

    <!-- Page CSS -->
    <link rel="stylesheet" href="/PROGNET/assets/admin/css/finance.css">
</head>

<body class="hm">
    <?php require_once __DIR__ . '/_partials/sidebarAdmin.html'; ?>
    <?php require_once __DIR__ . '/_partials/navbarAdmin.html'; ?>

    <div id="content-wrapper">

        <main class="cp-main container">

            <h1 class="cp-title">Finance</h1>

            <section class="finance-card">

                <div class="summary">
                    <div class="summary-item">
                        <span class="label">Our balance (IDR)</span>
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
                                <th>Customer Name</th>
                                <th>Reference code</th>
                                <th>Option</th>
                                <th>Purchase date</th>
                                <th>Net rate</th>
                                <th>Invoice</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($bookings)): ?>
                                <tr>
                                    <td colspan="8" style="text-align: center; padding: 32px; color: #6b7280;">
                                        No bookings found
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($bookings as $booking): ?>
                                    <tr>
                                        <td><?= formatDate($booking['activity_date']) ?></td>
                                        <td><?= htmlspecialchars($booking['booking_code']) ?></td>
                                        <td><?= htmlspecialchars($booking['customer_name']) ?></td>
                                        <td><?= htmlspecialchars($booking['reference_code']) ?></td>
                                        <td><?= ucfirst(htmlspecialchars($booking['option'])) ?></td>
                                        <td><?= formatDate($booking['purchase_date']) ?></td>
                                        <td class="net-rate"><?= formatIDR($booking['net_rate']) ?></td>
                                        <td>
                                            <button class="invoice-btn"
                                                onclick="viewInvoice(event, '<?= htmlspecialchars($booking['booking_code']) ?>', '<?= htmlspecialchars($booking['invoice_path'] ?? '') ?>', true)">
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
    <script src="/PROGNET/assets/admin/js/navbarAdmin.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="/PROGNET/assets/admin/js/finance.js"></script>
</body>

</html>