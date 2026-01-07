<?php
/**
 * Guest Guard
 * Protects auth pages (login, register, forgot-password) from logged-in users
 * Redirects to tours page if already authenticated
 */

ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['customer_id'])) {
    header("Location: /PROGNET/customer/tours.php");
    exit;
}
?>