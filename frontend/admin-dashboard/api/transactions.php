<?php
// admin-dashboard/api/transactions.php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
require_once '../../db.php';

$type   = $_GET['type']   ?? 'all';
$search = $_GET['search'] ?? '';

$where  = [];
$params = [];

if ($type !== 'all') {
    $where[]  = "t.type = :type";
    $params[':type'] = $type;
}
if ($search) {
    $where[]  = "(t.id::text ILIKE :search OR u.full_name ILIKE :search)";
    $params[':search'] = "%$search%";
}

$sql = "
    SELECT
        t.id,
        t.type,
        t.amount,
        t.status,
        t.payment_method,
        t.created_at,
        t.order_id,
        u.full_name AS user_name
    FROM transactions t
    LEFT JOIN users u ON t.user_id = u.id
";

if ($where) $sql .= " WHERE " . implode(" AND ", $where);
$sql .= " ORDER BY t.created_at DESC LIMIT 100";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
echo json_encode($stmt->fetchAll());
?>