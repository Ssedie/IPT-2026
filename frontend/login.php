<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

// 1. PostgreSQL Connection Settings (Matching your register.php)
$host     = "localhost";
$port     = "5432"; 
$dbname   = "php_database";
$user     = "postgres"; 
$password = "1234"; 

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;";
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    echo json_encode(["message" => "Connection failed: " . $e->getMessage()]);
    exit;
}

// 2. Get and Decode JSON Input
$data = json_decode(file_get_contents('php://input'), true);
$email = trim($data['email'] ?? '');
$pass  = $data['password'] ?? '';

if (empty($email) || empty($pass)) {
    echo json_encode(["message" => "Please enter both email and password."]);
    exit;
}

try {
    // 3. Search for user
    // Find user including the role column
    $stmt = $pdo->prepare("SELECT id, full_name, email, password, role FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($pass, $user['password'])) {
        unset($user['password']);
        echo json_encode([
            "success" => true,
            "user" => $user // This now contains the 'role'
        ]);
    } else {
        // Fail: Keep messages vague for security
        http_response_code(401);
        echo json_encode(["message" => "Invalid email or password."]);
        exit;
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["message" => "Database error: " . $e->getMessage()]);
}
?>