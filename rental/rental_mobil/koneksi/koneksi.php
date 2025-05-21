<?php
$host = 'localhost'; // or your server IP
$db   = 'rental_mobil1'; // your database name
$user = 'root'; // your database username
$pass = ''; // your database password
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $koneksi = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("Koneksi gagal: " . $e->getMessage());
}

return $koneksi;
?>