<?php
header("Content-Type: application/json");
// Connection settings (same as your login.php)
$host = "localhost"; $port = "5432"; $dbname = "php_database"; $user = "postgres"; $password = "1234";

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;";
    $pdo = new PDO($dsn, $user, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    // Fetch all users to display in the admin table
    $stmt = $pdo->query("SELECT id, full_name, email, city, role FROM users ORDER BY created_at DESC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($users);
} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>