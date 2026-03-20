<?php
// api/farmer_stats.php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
require_once '../../db.php';

$farmer_id = $_GET['farmer_id'] ?? null;
if (!$farmer_id) { echo json_encode(["error" => "Unauthorized"]); exit; }

try {
    // Total earnings this month
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(o.total_amount), 0) as total
        FROM orders o
        WHERE o.seller_id = ?
          AND o.status = 'delivered'
          AND DATE_TRUNC('month', o.created_at) = DATE_TRUNC('month', NOW())
    ");
    $stmt->execute([$farmer_id]);
    $earnings = $stmt->fetch()['total'];

    // Orders fulfilled (all time delivered)
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE seller_id = ? AND status = 'delivered'");
    $stmt->execute([$farmer_id]);
    $fulfilled = $stmt->fetch()['count'];

    // Pending orders
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE seller_id = ? AND status = 'pending'");
    $stmt->execute([$farmer_id]);
    $pending = $stmt->fetch()['count'];

    // Active listings
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM listings WHERE farmer_id = ? AND status = 'active'");
    $stmt->execute([$farmer_id]);
    $listings = $stmt->fetch()['count'];

    // Weekly sales (last 7 days grouped by day)
    $stmt = $pdo->prepare("
        SELECT TO_CHAR(created_at, 'Dy') as day, COALESCE(SUM(total_amount), 0) as total
        FROM orders
        WHERE seller_id = ? AND status = 'delivered'
          AND created_at >= NOW() - INTERVAL '7 days'
        GROUP BY DATE_TRUNC('day', created_at), TO_CHAR(created_at, 'Dy')
        ORDER BY DATE_TRUNC('day', created_at)
    ");
    $stmt->execute([$farmer_id]);
    $weekly = $stmt->fetchAll();

    // Monthly target progress (vs ₱30,000 default)
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(total_amount), 0) as total
        FROM orders WHERE seller_id = ?
        AND status = 'delivered'
        AND DATE_TRUNC('month', created_at) = DATE_TRUNC('month', NOW())
    ");
    $stmt->execute([$farmer_id]);
    $monthly = $stmt->fetch()['total'];

    echo json_encode([
        "earnings"       => number_format((float)$earnings, 2, '.', ''),
        "fulfilled"      => (int)$fulfilled,
        "pending"        => (int)$pending,
        "active_listings"=> (int)$listings,
        "weekly_sales"   => $weekly,
        "monthly_total"  => number_format((float)$monthly, 2, '.', ''),
    ]);
} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>