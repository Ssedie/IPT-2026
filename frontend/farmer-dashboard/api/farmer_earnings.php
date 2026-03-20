<?php
// api/farmer_earnings.php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
require_once '../../db.php';

$farmer_id = $_GET['farmer_id'] ?? null;
if (!$farmer_id) { echo json_encode(["error" => "Unauthorized"]); exit; }

try {
    // This month earnings
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(total_amount),0) as total FROM orders
        WHERE seller_id=? AND status='delivered'
        AND DATE_TRUNC('month',created_at)=DATE_TRUNC('month',NOW())
    ");
    $stmt->execute([$farmer_id]);
    $month_total = (float)$stmt->fetch()['total'];

    // Platform fee 5%
    $platform_fee = $month_total * 0.05;
    $net = $month_total - $platform_fee;

    // Pending payout (shipped orders not yet delivered)
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(total_amount),0) as total FROM orders
        WHERE seller_id=? AND status IN ('pending','shipped')
    ");
    $stmt->execute([$farmer_id]);
    $pending_payout = (float)$stmt->fetch()['total'];

    // Last payout (last delivered order batch)
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(total_amount),0) as total, MAX(created_at) as last_date
        FROM orders WHERE seller_id=? AND status='delivered'
        AND created_at >= NOW() - INTERVAL '30 days'
        AND created_at < DATE_TRUNC('month',NOW())
    ");
    $stmt->execute([$farmer_id]);
    $last = $stmt->fetch();

    // All-time total
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount),0) as total FROM orders WHERE seller_id=? AND status='delivered'");
    $stmt->execute([$farmer_id]);
    $all_time = (float)$stmt->fetch()['total'];

    // Monthly breakdown (last 6 months)
    $stmt = $pdo->prepare("
        SELECT TO_CHAR(DATE_TRUNC('month',created_at),'Mon YYYY') as month,
               COALESCE(SUM(total_amount),0) as total,
               COUNT(*) as order_count
        FROM orders WHERE seller_id=? AND status='delivered'
        AND created_at >= NOW() - INTERVAL '6 months'
        GROUP BY DATE_TRUNC('month',created_at)
        ORDER BY DATE_TRUNC('month',created_at) DESC
    ");
    $stmt->execute([$farmer_id]);
    $monthly = $stmt->fetchAll();

    // Recent transactions
    $stmt = $pdo->prepare("
        SELECT t.*, o.total_amount AS order_amount, u.full_name AS buyer_name, l.product_name
        FROM transactions t
        JOIN orders o   ON t.order_id  = o.id
        JOIN users u    ON o.buyer_id  = u.id
        JOIN listings l ON o.listing_id= l.id
        WHERE o.seller_id = ?
        ORDER BY t.created_at DESC
        LIMIT 20
    ");
    $stmt->execute([$farmer_id]);
    $transactions = $stmt->fetchAll();

    echo json_encode([
        "month_total"    => number_format($month_total,   2, '.', ''),
        "platform_fee"   => number_format($platform_fee,  2, '.', ''),
        "net_earnings"   => number_format($net,           2, '.', ''),
        "pending_payout" => number_format($pending_payout,2, '.', ''),
        "last_payout"    => number_format((float)($last['total']??0), 2, '.', ''),
        "last_payout_date"=> $last['last_date'] ?? null,
        "all_time"       => number_format($all_time,      2, '.', ''),
        "monthly_breakdown"=> $monthly,
        "transactions"   => $transactions,
    ]);
} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>