<?php
// admin-dashboard/api/audit.php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");
require_once '../../db.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $sql = "
        SELECT
            a.id,
            a.action,
            a.description,
            a.created_at,
            u.full_name AS admin_name
        FROM audit_log a
        LEFT JOIN users u ON a.admin_id = u.id
        ORDER BY a.created_at DESC
        LIMIT 50
    ";

    $stmt = $pdo->query($sql);
    echo json_encode($stmt->fetchAll());

} elseif ($method === 'POST') {
    // Called internally to log an admin action
    $data = json_decode(file_get_contents('php://input'), true);

    $admin_id   = $data['admin_id']   ?? null;
    $action     = $data['action']     ?? '';
    $description= $data['description']?? '';

    if (!$action) {
        http_response_code(400);
        echo json_encode(["error" => "Missing action"]);
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO audit_log (admin_id, action, description, created_at)
        VALUES (:admin_id, :action, :description, NOW())
    ");
    $stmt->execute([
        ':admin_id'    => $admin_id,
        ':action'      => $action,
        ':description' => $description,
    ]);

    echo json_encode(["success" => true]);
}
?>