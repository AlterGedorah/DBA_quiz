<?php
$host = '127.0.0.1';
$port = '5432';
$dbname = 'borrow_db';
$user = 'postgres';
$password = '3205';

$dsn = "pgsql:host=$host;port=$port;dbname='$dbname'";

try {
    $pdo = new PDO($dsn, $user, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}
?>