<?php
// api/farmer_profile.php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");
require_once '../../db.php';

$method    = $_SERVER['REQUEST_METHOD'];
$farmer_id = $_GET['farmer_id'] ?? null;
if (!$farmer_id) { echo json_encode(["error" => "Unauthorized"]); exit; }

try {
    if ($method === 'GET') {
        $stmt = $pdo->prepare("
            SELECT id, full_name, email, role, status,
                   barangay, city, province, street, zip_code, created_at
            FROM users WHERE id = ?
        ");
        $stmt->execute([$farmer_id]);
        $user = $stmt->fetch();

        // Application status
        $stmt2 = $pdo->prepare("SELECT status, farm_name, farm_size, crops FROM farmer_applications WHERE user_id=? ORDER BY created_at DESC LIMIT 1");
        $stmt2->execute([$farmer_id]);
        $app = $stmt2->fetch();

        // Stats
        $stmt3 = $pdo->prepare("SELECT COUNT(*) as c FROM listings WHERE farmer_id=? AND status='active'");
        $stmt3->execute([$farmer_id]);
        $active_listings = $stmt3->fetch()['c'];

        $stmt4 = $pdo->prepare("SELECT COUNT(*) as c FROM orders WHERE seller_id=? AND status='delivered'");
        $stmt4->execute([$farmer_id]);
        $fulfilled = $stmt4->fetch()['c'];

        echo json_encode([
            "user"            => $user,
            "application"     => $app,
            "active_listings" => (int)$active_listings,
            "fulfilled_orders"=> (int)$fulfilled,
        ]);

    } elseif ($method === 'POST') {
        $data      = json_decode(file_get_contents('php://input'), true);
        $full_name = trim($data['full_name'] ?? '');
        $street    = trim($data['street']    ?? '');
        $barangay  = trim($data['barangay']  ?? '');
        $city      = trim($data['city']      ?? '');
        $province  = trim($data['province']  ?? '');
        $zip_code  = trim($data['zip_code']  ?? '');

        if (!$full_name) { echo json_encode(["error" => "Full name required"]); exit; }

        if (!empty($data['new_password'])) {
            $old = $data['old_password'] ?? '';
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id=?");
            $stmt->execute([$farmer_id]);
            $row = $stmt->fetch();
            if (!password_verify($old, $row['password'])) {
                echo json_encode(["error" => "Current password is incorrect"]); exit;
            }
            $pdo->prepare("UPDATE users SET password=? WHERE id=?")
                ->execute([password_hash($data['new_password'], PASSWORD_DEFAULT), $farmer_id]);
        }

        $pdo->prepare("UPDATE users SET full_name=?,street=?,barangay=?,city=?,province=?,zip_code=? WHERE id=?")
            ->execute([$full_name, $street, $barangay, $city, $province, $zip_code, $farmer_id]);

        echo json_encode(["success" => true, "message" => "Profile updated"]);
    }
} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>