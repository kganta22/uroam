<?php
session_start();
require_once '../database/connect.php';
require_once '_guards/customerGuard.php';

// Get product ID from URL
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($product_id === 0) {
    header('Location: tours.php');
    exit;
}

// Fetch product details
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ? AND is_active = 1");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

if (!$product) {
    header('Location: tours.php');
    exit;
}

// Get product prices (private/group)
$stmt = $conn->prepare("SELECT * FROM product_prices WHERE product_id = ? ORDER BY category");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$prices = $result->fetch_all(MYSQLI_ASSOC);

$pageTitle = "Book Tour - " . htmlspecialchars($product['title']);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>

    <link rel="icon" type="image/uroam-icon" href="/PROGNET/images/uroam-title.ico">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Solway:wght@300;400;500;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="/PROGNET/assets/shared/css/style.css">
    <link rel="stylesheet" href="/PROGNET/assets/admin/css/sidebarAdmin.css">
    <link rel="stylesheet" href="/PROGNET/assets/customer/css/navbar.css">
    <link rel="stylesheet" href="/PROGNET/assets/customer/css/footer.css">
    <link rel="stylesheet" href="/PROGNET/assets/customer/css/book.css">
    <link rel="stylesheet" href="/PROGNET/assets/customer/css/custom-alert.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <!-- Sidebar -->
    <?php require_once __DIR__ . '/_partials/sidebarCustomer.html'; ?>

    <!-- Navbar -->
    <?php require_once __DIR__ . '/_partials/navbar.php'; ?>

    <div class="content-wrapper" data-product-id="<?= $product_id ?>">
        <div class="booking-container">
            <!-- Left Section - Tour Image and Options -->
            <div class="left-section">
                <!-- Tour Image -->
                <div class="tour-image-container">
                    <button class="back-button" onclick="window.history.back()">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <img src="<?= htmlspecialchars($product['thumbnail']) ?>"
                        alt="<?= htmlspecialchars($product['title']) ?>" class="tour-image">
                </div>

                <!-- Select Activity Date Button -->
                <button class="select-date-btn" id="selectDateBtn">
                    Select Activity Date & Time
                </button>

                <!-- Options Section -->
                <div class="options-section">
                    <div class="options-header">
                        <div class="options-title">
                            <i class="fas fa-hotel"></i>
                            <span>Options</span>
                        </div>
                        <div class="option-type-dropdown">
                            <select id="optionType" class="option-select">
                                <?php foreach ($prices as $price): ?>
                                    <option value="<?= $price['id'] ?>"
                                        data-type="<?= htmlspecialchars($price['category']) ?>"
                                        data-adult-price="<?= $price['adult_price'] ?>"
                                        data-child-price="<?= $price['child_price'] ?>">
                                        <?= ucfirst(htmlspecialchars($price['category'])) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                    </div>

                    <!-- Passengers -->
                    <div class="passengers-section">
                        <!-- Adults -->
                        <div class="passenger-row">
                            <div class="passenger-info">
                                <span class="passenger-label">Adult</span>
                                <span class="passenger-age">(+12 Years)</span>
                            </div>
                            <div class="quantity-controls">
                                <button class="quantity-btn minus" onclick="updateQuantity('adult', -1)">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <span class="quantity" id="adultQuantity">1</span>
                                <button class="quantity-btn plus" onclick="updateQuantity('adult', 1)">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Children -->
                        <div class="passenger-row">
                            <div class="passenger-info">
                                <span class="passenger-label">Children</span>
                                <span class="passenger-age">(1 - 12 Years)</span>
                            </div>
                            <div class="quantity-controls">
                                <button class="quantity-btn minus" onclick="updateQuantity('child', -1)">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <span class="quantity" id="childQuantity">0</span>
                                <button class="quantity-btn plus" onclick="updateQuantity('child', 1)">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Section - Price Details -->
            <div class="right-section">
                <div class="price-card">
                    <h2 class="price-title">Price detail</h2>

                    <!-- Price Breakdown -->
                    <div class="price-breakdown">
                        <div class="price-row header-row">
                            <span>Option</span>
                            <span id="selectedOptionType">Private</span>
                        </div>

                        <div class="price-items" id="priceItems">
                            <!-- Price items will be dynamically added here -->
                        </div>
                    </div>

                    <!-- Total Price Section -->
                    <div class="total-section">
                        <h3 class="total-title">Total price</h3>

                        <div class="booking-summary">
                            <div class="summary-item">
                                <i class="fas fa-hotel"></i>
                                <span id="summaryOption">-</span>
                            </div>
                            <div class="summary-item">
                                <i class="fas fa-calendar"></i>
                                <span id="summaryDate">-</span>
                            </div>
                        </div>

                        <div class="final-total">
                            <span>Total</span>
                            <span class="total-amount" id="totalAmount">Rp -</span>
                        </div>

                        <button class="continue-btn" onclick="proceedToBooking()">
                            Continue
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php require_once __DIR__ . '/_partials/footer.php'; ?>

    <!-- Date Picker Modal -->
    <div class="modal" id="dateModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Select Activity Date & Time</h3>
                <button class="close-modal" onclick="closeDateModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div style="margin-bottom: 15px;">
                    <label for="activityDate"
                        style="display: block; margin-bottom: 8px; font-weight: 500; color: #111827;">Date</label>
                    <input type="date" id="activityDate" class="date-input" min="<?= date('Y-m-d') ?>">
                </div>
                <div>
                    <label for="activityTime"
                        style="display: block; margin-bottom: 8px; font-weight: 500; color: #111827;">Time</label>
                    <input type="time" id="activityTime" class="date-input">
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-cancel" onclick="closeDateModal()">Cancel</button>
                <button class="btn-confirm" onclick="confirmDate()">Confirm</button>
            </div>
        </div>
    </div>

    <!-- Booking Confirmation Modal -->
    <div class="modal" id="confirmationModal">
        <div class="modal-content confirmation-modal">
            <div class="modal-header">
                <h3>Confirm Your Order</h3>
                <button class="close-modal" onclick="closeConfirmationModal()">&times;</button>
            </div>
            <div class="modal-body confirmation-body">
                <div class="confirmation-details">
                    <div class="confirmation-item">
                        <span class="confirmation-value"><?= htmlspecialchars($product['title']) ?></span>
                    </div>
                    <div class="confirmation-item">
                        <span class="confirmation-label">Option</span>
                        <span class="confirmation-value" id="confirmOptionType">Private</span>
                    </div>
                    <div class="confirmation-item">
                        <span class="confirmation-label">Passengers</span>
                        <span class="confirmation-value" id="confirmPassengers">1 Adult</span>
                    </div>
                    <div class="confirmation-item">
                        <span class="confirmation-label">Activity Date & Time</span>
                        <span class="confirmation-value" id="confirmDate">-</span>
                    </div>
                    <div class="confirmation-item total">
                        <span class="confirmation-label">Total Price</span>
                        <span class="confirmation-value price" id="confirmTotalPrice">Rp 0</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer confirmation-footer">
                <button class="btn-cancel" onclick="closeConfirmationModal()">Cancel</button>
                <button class="btn-confirm" onclick="confirmBooking()">Confirm & Continue</button>
            </div>
        </div>
    </div>

    <script src="/PROGNET/assets/admin/js/sidebarAdmin.js"></script>
    <script src="/PROGNET/assets/customer/js/custom-alert.js"></script>
    <script src="/PROGNET/assets/customer/js/book.js"></script>
</body>

</html>