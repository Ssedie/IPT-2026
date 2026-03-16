<?php
// admin-dashboard/api/disputes.php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");
require_once '../../db.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $status = $_GET['status'] ?? 'all';
    $params = [];

    $sql = "
        SELECT
            d.id,
            d.reason,
            d.status,
            d.priority,
            d.created_at,
            o.id         AS order_id,
            buyer.full_name  AS buyer_name,
            seller.full_name AS seller_name
        FROM disputes d
        JOIN orders o     ON d.order_id   = o.id
        JOIN users buyer  ON d.buyer_id   = buyer.id
        JOIN users seller ON d.seller_id  = seller.id
    ";

    if ($status !== 'all') {
        $sql .= " WHERE d.status = :status";
        $params[':status'] = $status;
    }

    $sql .= " ORDER BY d.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    echo json_encode($stmt->fetchAll());

} elseif ($method === 'POST') {
    $data       = json_decode(file_get_contents('php://input'), true);
    $dispute_id = $data['dispute_id'] ?? '';
    $action     = $data['action']     ?? '';

    if (!$dispute_id || !$action) {
        http_response_code(400);
        echo json_encode(["error" => "Missing dispute_id or action"]);
        exit;
    }

    $new_status = $action === 'resolve' ? 'resolved' : 'in_review';

    $stmt = $pdo->prepare("UPDATE disputes SET status = :status WHERE id = :id");
    $stmt->execute([':status' => $new_status, ':id' => $dispute_id]);

    echo json_encode(["success" => true, "new_status" => $new_status]);
}
?>