<?php
// db.php — shared database connection
// Place this in your frontend/ root (same level as login.php)

$host     = "localhost";
$port     = "5432";
$dbname   = "php_database";
$user     = "postgres";
$password = "1234";

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;";
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "DB connection failed: " . $e->getMessage()]);
    exit;
}
?>