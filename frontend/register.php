<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

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

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(["message" => "No data provided."]);
    exit;
}


$name     = trim($data['name'] ?? '');
$email    = trim($data['email'] ?? '');
$raw_pass = $data['password'] ?? '';


if (empty($name) || empty($email) || strlen($raw_pass) < 8) {
    echo json_encode(["message" => "Validation failed. Check name, email, and password length."]);
    exit;
}

try {
    $checkEmail = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $checkEmail->execute([$email]);
    
    if ($checkEmail->fetch()) {
        echo json_encode(["message" => "This email is already registered."]);
        exit;
    }

    $hashedPassword = password_hash($raw_pass, PASSWORD_BCRYPT);


    $sql = "INSERT INTO users (full_name, email, password, street, barangay, city, province, zip_code) 
            VALUES (:name, :email, :pass, :street, :barangay, :city, :province, :zip)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':name'     => $name,
        ':email'    => $email,
        ':pass'     => $hashedPassword,
        ':street'   => $data['street'] ?? '',
        ':barangay' => $data['barangay'] ?? '',
        ':city'     => $data['city'] ?? '',
        ':province' => $data['province'] ?? '',
        ':zip'      => $data['zip'] ?? ''
    ]);

    echo json_encode(["message" => "Account created successfully!"]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["message" => "Database error: " . $e->getMessage()]);
}
?>