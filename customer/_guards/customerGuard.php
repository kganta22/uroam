<?php
/**
 * Customer Guard
 * Protects pages that require customer to be logged in
 * Redirects to login page if not authenticated
 */

ob_start();

require_once __DIR__ . '/../../database/connect.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['customer_id'])) {
    header("Location: /PROGNET/customer/auth/login.php");
    exit;
}
?>