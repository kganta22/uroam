<?php
require_once __DIR__ . '/../database/connect.php';

// Get company profile info
$companyQuery = "SELECT policy_uroam FROM company_profile LIMIT 1";
$companyStmt = $conn->prepare($companyQuery);
$companyStmt->execute();
$companyResult = $companyStmt->get_result();
$company = $companyResult->fetch_assoc();
$companyStmt->close();

$policyContent = $company['policy_uroam'] ?? 'Content not available';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Policy - uRoam</title>

    <link rel="icon" type="image/uroam-icon" href="/PROGNET/images/uroam-title.ico">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Solway:wght@300;400;500;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="/PROGNET/assets/shared/css/style.css">
    <link rel="stylesheet" href="/PROGNET/assets/admin/css/sidebarAdmin.css">
    <link rel="stylesheet" href="/PROGNET/assets/customer/css/navbar.css">
    <link rel="stylesheet" href="/PROGNET/assets/customer/css/footer.css">
    <link rel="stylesheet" href="/PROGNET/assets/customer/css/company-info.css">
</head>

<body>
    <!-- Sidebar -->
    <?php require_once __DIR__ . '/_partials/sidebarCustomer.html'; ?>

    <!-- Navbar -->
    <?php require_once __DIR__ . '/_partials/navbar.php'; ?>

    <div class="content-wrapper">
        <main class="hm-main company-info-container">
            <section class="info-hero">
                <div class="info-header">
                    <button type="button" class="back-button" onclick="window.history.back()" aria-label="Back">
                        <img src="/PROGNET/images/icons/arrow-left.svg" alt="Back" width="24" height="24">
                    </button>
                    <h1 class="info-title">Policy</h1>
                </div>

                <div class="info-content">
                    <?= nl2br(htmlspecialchars($policyContent)) ?>
                </div>
            </section>
        </main>
    </div>

    <!-- Footer -->
    <?php require_once __DIR__ . '/_partials/footer.php'; ?>

    <script src="/PROGNET/assets/admin/js/sidebarAdmin.js"></script>
</body>

</html>