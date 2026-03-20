<?php
// api/listings.php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
require_once '../db.php';

$search   = $_GET['search']   ?? '';
$category = $_GET['category'] ?? '';
$sort     = $_GET['sort']     ?? 'newest';

try {
    $where  = "l.status = 'active'";
    $params = [];

    if ($search) {
        $where .= " AND (l.product_name ILIKE ? OR u.full_name ILIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    $order = match($sort) {
        'price_asc'  => "l.price ASC",
        'price_desc' => "l.price DESC",
        default      => "l.created_at DESC"
    };

    $stmt = $pdo->prepare("
        SELECT
            l.id,
            l.product_name,
            l.price,
            l.stock,
            l.unit,
            l.created_at,
            u.id        AS farmer_id,
            u.full_name AS farmer_name,
            u.city      AS farmer_city,
            u.province  AS farmer_province
        FROM listings l
        JOIN users u ON l.farmer_id = u.id
        WHERE $where
        ORDER BY $order
        LIMIT 100
    ");
    $stmt->execute($params);
    $listings = $stmt->fetchAll();

    echo json_encode(["listings" => $listings]);
} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>