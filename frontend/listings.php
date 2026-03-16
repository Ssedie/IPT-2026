<?php
// admin-dashboard/api/listings.php
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
            l.id,
            l.product_name,
            l.price,
            l.stock,
            l.status,
            l.flag_reason,
            l.created_at,
            u.full_name AS farmer_name
        FROM listings l
        JOIN users u ON l.farmer_id = u.id
    ";

    if ($status !== 'all') {
        $sql .= " WHERE l.status = :status";
        $params[':status'] = $status;
    }

    $sql .= " ORDER BY l.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    echo json_encode($stmt->fetchAll());

} elseif ($method === 'POST') {
    $data       = json_decode(file_get_contents('php://input'), true);
    $listing_id = $data['listing_id'] ?? '';
    $action     = $data['action']     ?? ''; // 'approve' or 'remove'

    if (!$listing_id || !$action) {
        http_response_code(400);
        echo json_encode(["error" => "Missing listing_id or action"]);
        exit;
    }

    $new_status = $action === 'approve' ? 'active' : 'removed';

    $stmt = $pdo->prepare("UPDATE listings SET status = :status WHERE id = :id");
    $stmt->execute([':status' => $new_status, ':id' => $listing_id]);

    echo json_encode(["success" => true, "new_status" => $new_status]);
}
?>