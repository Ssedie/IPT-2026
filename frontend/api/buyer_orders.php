<?php
// api/buyer_orders.php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
require_once '../db.php';

$buyer_id = $_GET['buyer_id'] ?? null;
$status   = $_GET['status']   ?? null; // optional filter

if (!$buyer_id) { echo json_encode(["error" => "Unauthorized"]); exit; }

try {
    $where = "o.buyer_id = ?";
    $params = [$buyer_id];

    if ($status === 'active') {
        $where .= " AND o.status IN ('pending','shipped')";
    } elseif ($status === 'history') {
        $where .= " AND o.status IN ('delivered','cancelled','disputed')";
    }

    $stmt = $pdo->prepare("
        SELECT
            o.id,
            o.status,
            o.total_amount,
            o.created_at,
            l.product_name,
            l.unit,
            u.full_name AS seller_name
        FROM orders o
        JOIN listings l ON o.listing_id = l.id
        JOIN users u    ON o.seller_id  = u.id
        WHERE $where
        ORDER BY o.created_at DESC
        LIMIT 50
    ");
    $stmt->execute($params);
    $orders = $stmt->fetchAll();

    echo json_encode(["orders" => $orders]);
} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>