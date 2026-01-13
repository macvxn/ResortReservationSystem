<?php
$is_termux = true;

if ($is_termux) {
    $host = "127.0.0.1";
    $user = "root";
    $pass = "root";
} else {
    $host = "localhost";
    $user = "root";
    $pass = "";
}

$dbname  = "resort_reservation_db";  // Changed to our database name
$charset = "utf8mb4";

$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    error_log($e->getMessage());
    die("Database connection error. Check your MySQL service.");
}
  ?>