<?php
// admin-dashboard/api/prices.php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
require_once '../../db.php';

// Gets average listing price per crop type, compared to last week
$sql = "
    SELECT
        product_name,
        ROUND(AVG(price)::numeric, 2) AS current_price,
        COUNT(*) AS listing_count
    FROM listings
    WHERE status = 'active'
    GROUP BY product_name
    ORDER BY listing_count DESC
";

$stmt = $pdo->query($sql);
echo json_encode($stmt->fetchAll());
?>