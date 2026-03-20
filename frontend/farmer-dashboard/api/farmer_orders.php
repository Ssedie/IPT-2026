<?php
// api/farmer_orders.php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");
require_once '../../db.php';

$method    = $_SERVER['REQUEST_METHOD'];
$farmer_id = $_GET['farmer_id'] ?? null;
if (!$farmer_id) { echo json_encode(["error" => "Unauthorized"]); exit; }

try {
    if ($method === 'GET') {
        $filter = $_GET['filter'] ?? 'incoming'; // incoming | history | all

        $where = "o.seller_id = ?";
        $params = [$farmer_id];

        if ($filter === 'incoming') {
            $where .= " AND o.status IN ('pending','shipped')";
        } elseif ($filter === 'history') {
            $where .= " AND o.status IN ('delivered','cancelled','disputed')";
        }

        $stmt = $pdo->prepare("
            SELECT
                o.id, o.status, o.total_amount, o.created_at,
                l.product_name, l.unit,
                u.id AS buyer_id, u.full_name AS buyer_name,
                u.city AS buyer_city
            FROM orders o
            JOIN listings l ON o.listing_id = l.id
            JOIN users u    ON o.buyer_id   = u.id
            WHERE $where
            ORDER BY o.created_at DESC
            LIMIT 100
        ");
        $stmt->execute($params);
        echo json_encode(["orders" => $stmt->fetchAll()]);

    } elseif ($method === 'POST') {
        // Update order status: pending→shipped, shipped→delivered
        $data      = json_decode(file_get_contents('php://input'), true);
        $order_id  = $data['order_id']  ?? null;
        $action    = $data['action']    ?? null; // 'ship' | 'deliver'

        if (!$order_id || !$action) { echo json_encode(["error" => "Missing params"]); exit; }

        // Verify order belongs to this farmer
        $stmt = $pdo->prepare("SELECT id, status FROM orders WHERE id = ? AND seller_id = ?");
        $stmt->execute([$order_id, $farmer_id]);
        $order = $stmt->fetch();
        if (!$order) { echo json_encode(["error" => "Order not found"]); exit; }

        $newStatus = match($action) {
            'ship'    => 'shipped',
            'deliver' => 'delivered',
            default   => null
        };

        if (!$newStatus) { echo json_encode(["error" => "Invalid action"]); exit; }

        // Validate transition
        $allowed = ['ship' => 'pending', 'deliver' => 'shipped'];
        if ($order['status'] !== $allowed[$action]) {
            echo json_encode(["error" => "Cannot perform this action on a {$order['status']} order"]); exit;
        }

        $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?")->execute([$newStatus, $order_id]);

        // Insert notification for buyer
        $stmt = $pdo->prepare("SELECT buyer_id FROM orders WHERE id = ?");
        $stmt->execute([$order_id]);
        $buyer_id = $stmt->fetch()['buyer_id'];

        $messages = [
            'shipped'   => ["order", "Order Shipped 🚚", "Your order #{$order_id} has been shipped and is on its way!"],
            'delivered' => ["order", "Order Delivered ✓", "Your order #{$order_id} has been marked as delivered."],
        ];
        [$type, $title, $body] = $messages[$newStatus];

        $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
            id SERIAL PRIMARY KEY, user_id INT REFERENCES users(id) ON DELETE CASCADE,
            type VARCHAR(50), title VARCHAR(200), body TEXT,
            is_read BOOLEAN DEFAULT FALSE, created_at TIMESTAMP DEFAULT NOW()
        )");
        $pdo->prepare("INSERT INTO notifications (user_id,type,title,body) VALUES (?,?,?,?)")
            ->execute([$buyer_id, $type, $title, $body]);

        echo json_encode(["success" => true, "message" => "Order marked as $newStatus"]);
    }
} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>