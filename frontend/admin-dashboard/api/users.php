<?php
// admin-dashboard/api/users.php
// GET  → list users (with optional ?role=farmer&search=name)
// POST → suspend or reinstate a user { action: 'suspend'|'reinstate', user_id: X }

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once '../../db.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $role   = $_GET['role']   ?? '';
    $search = $_GET['search'] ?? '';

    $where  = [];
    $params = [];

    if ($role && $role !== 'all') {
        $where[]  = "role = :role";
        $params[':role'] = $role;
    }

    if ($search) {
        $where[]  = "(full_name ILIKE :search OR email ILIKE :search)";
        $params[':search'] = "%$search%";
    }

    $sql = "SELECT id, full_name, email, role, status, created_at FROM users";
    if ($where) $sql .= " WHERE " . implode(" AND ", $where);
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

    $new_status = $action === 'suspend' ? 'suspended' : 'active';

    $stmt = $pdo->prepare("UPDATE users SET status = :status WHERE id = :id");
    $stmt->execute([':status' => $new_status, ':id' => $user_id]);

    echo json_encode(["success" => true, "new_status" => $new_status]);
}
?>