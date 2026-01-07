<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../database/connect.php';

// Get request type
$action = $_GET['action'] ?? '';
$response = [];

try {
    switch ($action) {
        case 'get_categories':
            $response = getCategories();
            break;

        case 'get_products':
            $response = getProducts();
            break;

        case 'search':
            $search = $_GET['q'] ?? '';
            $response = searchProducts($search);
            break;

        case 'filter_by_category':
            $categoryId = intval($_GET['category_id'] ?? 0);
            $response = filterByCategory($categoryId);
            break;

        case 'get_top_products':
            $response = getTopProducts();
            break;

        default:
            $response = ['success' => false, 'message' => 'Invalid action'];
    }
} catch (Exception $e) {
    $response = ['success' => false, 'message' => $e->getMessage()];
}

echo json_encode($response);

// ============================================
// FUNCTIONS
// ============================================

function getCategories()
{
    global $conn;

    $query = "SELECT id, name FROM categories ORDER BY name ASC";
    $result = $conn->query($query);

    if (!$result) {
        return ['success' => false, 'message' => $conn->error];
    }

    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }

    return [
        'success' => true,
        'data' => $categories
    ];
}

function getProducts()
{
    global $conn;

    $query = "
        SELECT 
            p.id,
            p.title,
            p.thumbnail,
            p.duration_hours,
            MIN(pp.child_price) as child_price
        FROM products p
        LEFT JOIN product_prices pp ON p.id = pp.product_id
        WHERE p.is_active = 1
        GROUP BY p.id
        ORDER BY p.id DESC
        LIMIT 12
    ";

    $result = $conn->query($query);

    if (!$result) {
        return ['success' => false, 'message' => $conn->error];
    }

    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = formatProductData($row);
    }

    return [
        'success' => true,
        'data' => $products
    ];
}

function searchProducts($search)
{
    global $conn;

    $search = '%' . $conn->real_escape_string($search) . '%';

    $query = "
        SELECT 
            p.id,
            p.title,
            p.thumbnail,
            p.duration_hours,
            MIN(pp.child_price) as child_price
        FROM products p
        LEFT JOIN product_prices pp ON p.id = pp.product_id
        WHERE p.title LIKE ? AND p.is_active = 1
        GROUP BY p.id
        ORDER BY p.id DESC
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $search);
    $stmt->execute();
    $result = $stmt->get_result();

    if (!$result) {
        return ['success' => false, 'message' => $conn->error];
    }

    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = formatProductData($row);
    }

    $stmt->close();

    return [
        'success' => true,
        'data' => $products,
        'count' => count($products)
    ];
}

function filterByCategory($categoryId)
{
    global $conn;

    $query = "
        SELECT 
            p.id,
            p.title,
            p.thumbnail,
            p.duration_hours,
            MIN(pp.child_price) as child_price
        FROM products p
        INNER JOIN product_categories pc ON p.id = pc.product_id
        LEFT JOIN product_prices pp ON p.id = pp.product_id
        WHERE pc.category_id = ? AND p.is_active = 1
        GROUP BY p.id
        ORDER BY p.id DESC
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $categoryId);
    $stmt->execute();
    $result = $stmt->get_result();

    if (!$result) {
        return ['success' => false, 'message' => $conn->error];
    }

    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = formatProductData($row);
    }

    $stmt->close();

    return [
        'success' => true,
        'data' => $products,
        'count' => count($products)
    ];
}

function getTopProducts()
{
    global $conn;

    $query = "
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

    $result = $conn->query($query);

    if (!$result) {
        return ['success' => false, 'message' => $conn->error];
    }

    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = formatProductData($row);
    }

    return [
        'success' => true,
        'data' => $products
    ];
}

function formatProductData($row)
{
    return [
        'id' => $row['id'],
        'title' => $row['title'],
        'thumbnail' => $row['thumbnail'],
        'duration_hours' => $row['duration_hours'],
        'price' => intval($row['child_price'] ?? 0)
    ];
}
?>