<?php
// api/disputes.php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");
require_once '../../db.php';

$method   = $_SERVER['REQUEST_METHOD'];
$buyer_id = $_GET['buyer_id'] ?? null;

if (!$buyer_id) { echo json_encode(["error" => "Unauthorized"]); exit; }

try {
    if ($method === 'GET') {
        $stmt = $pdo->prepare("
            SELECT d.id, d.reason, d.status, d.priority, d.created_at,
                   o.total_amount, o.status AS order_status,
                   l.product_name,
                   u.full_name AS seller_name
            FROM disputes d
            JOIN orders o   ON d.order_id  = o.id
            JOIN listings l ON o.listing_id = l.id
            JOIN users u    ON d.seller_id  = u.id
            WHERE d.buyer_id = ?
            ORDER BY d.created_at DESC
        ");
        $stmt->execute([$buyer_id]);
        echo json_encode(["disputes" => $stmt->fetchAll()]);

    } elseif ($method === 'POST') {
        $data     = json_decode(file_get_contents('php://input'), true);
        $order_id = $data['order_id'] ?? null;
        $reason   = trim($data['reason'] ?? '');

        if (!$order_id || !$reason) { echo json_encode(["error" => "Missing fields"]); exit; }

        // Verify order belongs to buyer
        $stmt = $pdo->prepare("SELECT seller_id, status FROM orders WHERE id = ? AND buyer_id = ?");
        $stmt->execute([$order_id, $buyer_id]);
        $order = $stmt->fetch();

        if (!$order) { echo json_encode(["error" => "Order not found"]); exit; }
        if (in_array($order['status'], ['cancelled'])) {
            echo json_encode(["error" => "Cannot dispute a cancelled order"]); exit;
        }

        // Check not already disputed
        $stmt = $pdo->prepare("SELECT id FROM disputes WHERE order_id = ?");
        $stmt->execute([$order_id]);
        if ($stmt->fetch()) { echo json_encode(["error" => "Dispute already filed for this order"]); exit; }

        $pdo->prepare("
            INSERT INTO disputes (order_id, buyer_id, seller_id, reason, status, priority)
            VALUES (?, ?, ?, ?, 'open', 'normal')
        ")->execute([$order_id, $buyer_id, $order['seller_id'], $reason]);

        // Update order status
        $pdo->prepare("UPDATE orders SET status = 'disputed' WHERE id = ?")->execute([$order_id]);

        echo json_encode(["success" => true, "message" => "Dispute filed successfully"]);
    }
} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>