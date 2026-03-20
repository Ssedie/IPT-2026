<?php
// api/buyer_stats.php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
require_once '../db.php';

$user = json_decode(file_get_contents('php://input'), true);
$buyer_id = $user['id'] ?? ($_GET['buyer_id'] ?? null);

if (!$buyer_id) { echo json_encode(["error" => "Unauthorized"]); exit; }

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE buyer_id = ? AND status IN ('pending','shipped')");
    $stmt->execute([$buyer_id]);
    $active_orders = $stmt->fetch()['count'];

    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE buyer_id = ? AND status = 'delivered'");
    $stmt->execute([$buyer_id]);
    $completed_orders = $stmt->fetch()['count'];

    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM saved_listings WHERE buyer_id = ?");
    $stmt->execute([$buyer_id]);
    $saved = $stmt->fetch()['count'];

    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(total_amount), 0) as total FROM orders
        WHERE buyer_id = ? AND status = 'delivered'
        AND DATE_TRUNC('month', created_at) = DATE_TRUNC('month', NOW())
    ");
    $stmt->execute([$buyer_id]);
    $total_spent = $stmt->fetch()['total'];

    echo json_encode([
        "active_orders"    => (int)$active_orders,
        "completed_orders" => (int)$completed_orders,
        "saved_listings"   => (int)$saved,
        "total_spent"      => number_format((float)$total_spent, 2, '.', '')
    ]);
} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>