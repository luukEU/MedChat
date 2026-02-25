<?php
// Databse connectie 
$host = "localhost";
$db   = "medchat_demo";
$user = "root";
$pass = ""; // Leeg want gebruik root 
$charset = "utf8mb4";

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
  PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
  $pdo = new PDO($dsn, $user, $pass, $options);
} catch (Exception $e) {
  http_response_code(500);
  echo "Database connectie mislukt.";
  exit;
}