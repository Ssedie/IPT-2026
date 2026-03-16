<?php
// admin-dashboard/api/stats.php
// Returns all KPI numbers for the main dashboard

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

require_once '../../db.php'; // goes up to frontend/ root

try {
    // Total registered farmers
    $farmers = $pdo->query("SELECT COUNT(*) AS count FROM users WHERE role = 'farmer'")->fetch()['count'];

    // Total buyers
    $buyers = $pdo->query("SELECT COUNT(*) AS count FROM users WHERE role = 'buyer'")->fetch()['count'];

    // Active listings
    $listings = $pdo->query("SELECT COUNT(*) AS count FROM listings WHERE status = 'active'")->fetch()['count'];

    // Orders this month
    $orders = $pdo->query("
        SELECT COUNT(*) AS count FROM orders
        WHERE DATE_TRUNC('month', created_at) = DATE_TRUNC('month', CURRENT_DATE)
    ")->fetch()['count'];

    // Open disputes
    $disputes = $pdo->query("SELECT COUNT(*) AS count FROM disputes WHERE status IN ('open', 'urgent')")->fetch()['count'];

    // Revenue this month
    $revenue = $pdo->query("
        SELECT COALESCE(SUM(total_amount), 0) AS total FROM orders
        WHERE DATE_TRUNC('month', created_at) = DATE_TRUNC('month', CURRENT_DATE)
        AND status != 'cancelled'
    ")->fetch()['total'];

    // Pending farmer applications
    $pending_apps = $pdo->query("SELECT COUNT(*) AS count FROM users WHERE role = 'farmer' AND status = 'pending'")->fetch()['count'];

    echo json_encode([
        "farmers"       => (int)$farmers,
        "buyers"        => (int)$buyers,
        "listings"      => (int)$listings,
        "orders"        => (int)$orders,
        "disputes"      => (int)$disputes,
        "revenue"       => (float)$revenue,
        "pending_apps"  => (int)$pending_apps,
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
?>