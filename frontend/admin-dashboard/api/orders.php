<?php
// admin-dashboard/api/orders.php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
require_once '../../db.php';

$status = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';

$where  = [];
$params = [];

if ($status !== 'all') {
    $where[]  = "o.status = :status";
    $params[':status'] = $status;
}
if ($search) {
    $where[]  = "(o.id::text ILIKE :search OR buyer.full_name ILIKE :search)";
    $params[':search'] = "%$search%";
}

$sql = "
    SELECT
        o.id,
        o.status,
        o.total_amount,
        o.created_at,
        buyer.full_name  AS buyer_name,
        seller.full_name AS seller_name
    FROM orders o
    JOIN users buyer  ON o.buyer_id  = buyer.id
    JOIN users seller ON o.seller_id = seller.id
";

if ($where) $sql .= " WHERE " . implode(" AND ", $where);
$sql .= " ORDER BY o.created_at DESC LIMIT 100";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
echo json_encode($stmt->fetchAll());
?>