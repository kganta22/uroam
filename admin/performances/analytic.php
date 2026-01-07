<?php
require_once __DIR__ . '/../init.php';

// Fetch products for filter
$products = [];
$res = $conn->query("SELECT id, title FROM products ORDER BY title ASC");
while ($row = $res->fetch_assoc()) {
    $products[] = $row;
}

// Parse filters
$selectedProducts = [];
if (isset($_GET['products']) && is_array($_GET['products'])) {
    foreach ($_GET['products'] as $pid) {
        if (is_numeric($pid)) {
            $selectedProducts[] = (int) $pid;
        }
    }
}

$dateStart = isset($_GET['date_start']) && preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $_GET['date_start']) ? $_GET['date_start'] : null;
$dateEnd = isset($_GET['date_end']) && preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $_GET['date_end']) ? $_GET['date_end'] : null;

// Build WHERE for bookings
$whereBooking = [];
$paramsBooking = [];
$typesBooking = '';

if ($dateStart) {
    $whereBooking[] = 'activity_date >= ?';
    $paramsBooking[] = $dateStart;
    $typesBooking .= 's';
}
if ($dateEnd) {
    $whereBooking[] = 'activity_date <= ?';
    $paramsBooking[] = $dateEnd;
    $typesBooking .= 's';
}
if (!empty($selectedProducts)) {
    $placeholders = implode(',', array_fill(0, count($selectedProducts), '?'));
    $whereBooking[] = "product_id IN ($placeholders)";
    foreach ($selectedProducts as $pid) {
        $paramsBooking[] = $pid;
        $typesBooking .= 'i';
    }
}

$whereBookingSql = $whereBooking ? ('WHERE ' . implode(' AND ', $whereBooking)) : '';

// Helper to run scalar query
$fetchScalar = function ($sql, $types, $params) use ($conn) {
    $val = null;
    if ($stmt = $conn->prepare($sql)) {
        if ($types !== '' && !empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $stmt->bind_result($val);
        $stmt->fetch();
        $stmt->close();
        return $val ?? 0;
    }
    return 0;
};

// KPI calculations
$kpiRevenue = $fetchScalar("SELECT COALESCE(SUM(net_rate),0) FROM bookings $whereBookingSql", $typesBooking, $paramsBooking);
$kpiBookings = $fetchScalar("SELECT COUNT(*) FROM bookings $whereBookingSql", $typesBooking, $paramsBooking);
$kpiCustomers = $fetchScalar("SELECT COALESCE(SUM(total_adult + total_child),0) FROM bookings $whereBookingSql", $typesBooking, $paramsBooking);

// Canceled requests (order_request status reject)
$whereCancel = $whereBooking;
$paramsCancel = $paramsBooking;
$typesCancel = $typesBooking;
$whereCancel[] = "status = 'reject'";
$whereCancelSql = 'WHERE ' . implode(' AND ', $whereCancel);
$kpiCanceled = $fetchScalar("SELECT COUNT(*) FROM order_request $whereCancelSql", $typesCancel, $paramsCancel);

// Line chart data (activity_date)
$lineLabels = [];
$lineRevenue = [];
$lineTickets = [];
$sqlLine = "SELECT activity_date, SUM(net_rate) AS revenue, SUM(total_adult + total_child) AS tickets
            FROM bookings
            $whereBookingSql
            GROUP BY activity_date
            ORDER BY activity_date";
if ($stmt = $conn->prepare($sqlLine)) {
    if ($typesBooking !== '' && !empty($paramsBooking)) {
        $stmt->bind_param($typesBooking, ...$paramsBooking);
    }
    $stmt->execute();
    $stmt->bind_result($d, $rev, $tkt);
    while ($stmt->fetch()) {
        $lineLabels[] = $d;
        $lineRevenue[] = (float) $rev;
        $lineTickets[] = (float) $tkt;
    }
    $stmt->close();
}

// Market chart data (top 5 countries)
$marketLabels = [];
$marketRevenue = [];
$sqlMarket = "SELECT COALESCE(c.country, 'Unknown') AS country, SUM(b.net_rate) AS revenue
              FROM bookings b
              LEFT JOIN customers c ON b.customer_id = c.id
              $whereBookingSql
              GROUP BY country
              ORDER BY revenue DESC
              LIMIT 5";
if ($stmt = $conn->prepare($sqlMarket)) {
    if ($typesBooking !== '' && !empty($paramsBooking)) {
        $stmt->bind_param($typesBooking, ...$paramsBooking);
    }
    $stmt->execute();
    $stmt->bind_result($country, $rev);
    while ($stmt->fetch()) {
        $marketLabels[] = $country;
        $marketRevenue[] = (float) $rev;
    }
    $stmt->close();
}

$analyticsData = [
    'kpis' => [
        'revenue' => (float) $kpiRevenue,
        'bookings' => (int) $kpiBookings,
        'customers' => (int) $kpiCustomers,
        'canceled' => (int) $kpiCanceled,
    ],
    'line' => [
        'labels' => $lineLabels,
        'revenue' => $lineRevenue,
        'tickets' => $lineTickets,
    ],
    'market' => [
        'labels' => $marketLabels,
        'revenue' => $marketRevenue,
    ],
];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Analytics</title>
    <link rel="icon" type="image/uroam-icon" href="/PROGNET/images/uroam-title.ico">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Flatpickr for date picker -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Solway:wght@300;400;500;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="/PROGNET/assets/shared/css/style.css">
    <link rel="stylesheet" href="/PROGNET/assets/shared/css/filter-product.css">
    <link rel="stylesheet" href="/PROGNET/assets/admin/css/sidebarAdmin.css">
    <link rel="stylesheet" href="/PROGNET/assets/admin/css/navbarAdmin.css">
    <link rel="stylesheet" href="/PROGNET/assets/admin/css/bookings.css">
    <link rel="stylesheet" href="/PROGNET/assets/admin/css/analytic.css">
</head>

<body>
    <?php require_once __DIR__ . '/../_partials/sidebarAdmin.html'; ?>
    <?php require_once __DIR__ . '/../_partials/navbarAdmin.html'; ?>
    <div id="content-wrapper">

        <main class="container">

            <h1 class="page-title">Analytics</h1>

            <!-- Filters -->
            <section class="an-filters">
                <form id="filterForm" method="GET">
                    <!-- Product Filter -->
                    <?php require_once __DIR__ . '/../../assets/components/filter-product.php'; ?>

                    <!-- Date Range Filter -->
                    <div class="bk-filter-block an-date-block">
                        <div class="bk-filter-header">
                            <label class="bk-label bk-filter-label">Date Range</label>
                            <a class="bk-clear" onclick="clearAnalyticsDate()"
                                style="display: <?= (!empty($_GET['date_start']) || !empty($_GET['date_end'])) ? 'inline' : 'none' ?>;">
                                Clear
                            </a>
                        </div>

                        <div class="bk-date-input">
                            <input type="text" id="dateRange" class="bk-input bk-filter-input"
                                placeholder="Select date range" readonly>
                            <img src="/PROGNET/images/icons/calendar.svg" class="bk-calendar-icon" alt="calendar">
                        </div>

                        <!-- Hidden inputs for PHP -->
                        <input type="hidden" name="date_start" id="date_start" value="<?= $_GET['date_start'] ?? '' ?>">
                        <input type="hidden" name="date_end" id="date_end" value="<?= $_GET['date_end'] ?? '' ?>">
                    </div>
                </form>
            </section>

            <!-- KPI -->
            <section class="kpis">
                <div class="kpi">
                    <span class="label">Revenue</span>
                    <span class="value">IDR <?= number_format($analyticsData['kpis']['revenue']) ?></span>
                </div>
                <div class="kpi">
                    <span class="label">Bookings</span>
                    <span class="value"><?= $analyticsData['kpis']['bookings'] ?></span>
                </div>
                <div class="kpi">
                    <span class="label">Total Customers</span>
                    <span class="value"><?= $analyticsData['kpis']['customers'] ?></span>
                </div>
                <div class="kpi">
                    <span class="label">Canceled Requests</span>
                    <span class="value"><?= $analyticsData['kpis']['canceled'] ?></span>
                </div>
            </section>

            <!-- Charts -->
            <section class="charts">

                <div class="chart-card">
                    <h3>Revenue and tickets booked by travel date</h3>
                    <canvas id="revenueChart"></canvas>
                </div>

                <div class="chart-card">
                    <h3>Top 5 international source markets by revenue</h3>
                    <canvas id="marketChart"></canvas>
                </div>

            </section>

        </main>
    </div>

    <script src="/PROGNET/assets/shared/js/filter-product.js"></script>
    <script>
        window.analyticsData = <?= json_encode($analyticsData, JSON_UNESCAPED_SLASHES) ?>;
    </script>
    <script src="/PROGNET/assets/admin/js/sidebarAdmin.js"></script>
    <script src="/PROGNET/assets/admin/js/navbarAdmin.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="/PROGNET/assets/admin/js/analytic.js"></script>
</body>

</html>