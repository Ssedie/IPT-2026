<?php
// api/buyer_profile.php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");
require_once '../db.php';

$method  = $_SERVER['REQUEST_METHOD'];
$user_id = $_GET['user_id'] ?? null;

if (!$user_id) { echo json_encode(["error" => "Unauthorized"]); exit; }

try {
    if ($method === 'GET') {
        $stmt = $pdo->prepare("
            SELECT id, full_name, email, role, status,
                   barangay, city, province, street, zip_code, created_at
            FROM users WHERE id = ?
        ");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        if (!$user) { echo json_encode(["error" => "User not found"]); exit; }
        echo json_encode(["user" => $user]);

    } elseif ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);

        $full_name = trim($data['full_name'] ?? '');
        $street    = trim($data['street']    ?? '');
        $barangay  = trim($data['barangay']  ?? '');
        $city      = trim($data['city']      ?? '');
        $province  = trim($data['province']  ?? '');
        $zip_code  = trim($data['zip_code']  ?? '');

        if (!$full_name) { echo json_encode(["error" => "Full name required"]); exit; }

        // Password change (optional)
        if (!empty($data['new_password'])) {
            $old = $data['old_password'] ?? '';
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $row = $stmt->fetch();
            if (!password_verify($old, $row['password'])) {
                echo json_encode(["error" => "Current password is incorrect"]); exit;
            }
            $hashed = password_hash($data['new_password'], PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hashed, $user_id]);
        }

        $pdo->prepare("
            UPDATE users
            SET full_name=?, street=?, barangay=?, city=?, province=?, zip_code=?
            WHERE id=?
        ")->execute([$full_name, $street, $barangay, $city, $province, $zip_code, $user_id]);

        echo json_encode(["success" => true, "message" => "Profile updated"]);
    }
} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>