<?php
// admin-dashboard/api/applications.php
// GET  → list farmer applications (optional ?status=pending)
// POST → approve or reject { action: 'approve'|'reject', user_id: X }

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once '../../db.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $status = $_GET['status'] ?? 'all';

    $sql = "
        SELECT id, full_name, email, status, created_at,
               barangay, city, province
        FROM users
        WHERE role = 'farmer'
    ";

    $params = [];
    if ($status !== 'all') {
        $sql .= " AND status = :status";
        $params[':status'] = $status;
    }

    $sql .= " ORDER BY created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    echo json_encode($stmt->fetchAll());

} elseif ($method === 'POST') {
    $data    = json_decode(file_get_contents('php://input'), true);
    $action  = $data['action']  ?? '';
    $user_id = $data['user_id'] ?? '';

    if (!$action || !$user_id) {
        http_response_code(400);
        echo json_encode(["error" => "Missing action or user_id"]);
        exit;
    }

    $new_status = $action === 'approve' ? 'active' : 'rejected';

    $stmt = $pdo->prepare("UPDATE users SET status = :status WHERE id = :id AND role = 'farmer'");
    $stmt->execute([':status' => $new_status, ':id' => $user_id]);

    echo json_encode(["success" => true, "new_status" => $new_status]);
}
?>