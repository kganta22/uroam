<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'uroam';
$port = '3306';
$conn = new mysqli($host, $user, $pass, $dbname, $port);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
?>