<?php
require __DIR__.'/env.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT); // lempar exception
$host = $ENV['DB_HOST'];
$user = $ENV['DB_USER'];
$pass = $ENV['DB_PASS'];
$db   = $ENV['DB_NAME'];

$koneksi = mysqli_connect($host, $user, $pass, $db);
if (!$koneksi) {
    die('Koneksi DB gagal: ' . mysqli_connect_error());
}

$koneksi->set_charset('utf8mb4');
