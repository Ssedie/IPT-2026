<?php
// api/buyer_order_action.php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
require_once '../db.php';

$data      = json_decode(file_get_contents('php://input'), true);
$buyer_id  = $data['buyer_id']  ?? null;
$order_id  = $data['order_id']  ?? null;
$action    = $data['action']    ?? null; // 'cancel' | 'confirm_delivery'

if (!$buyer_id || !$order_id || !$action) {
    echo json_encode(["error" => "Missing parameters"]); exit;
}

try {
    // Verify order belongs to buyer
    $stmt = $pdo->prepare("SELECT id, status FROM orders WHERE id = ? AND buyer_id = ?");
    $stmt->execute([$order_id, $buyer_id]);
    $order = $stmt->fetch();

    if (!$order) { echo json_encode(["error" => "Order not found"]); exit; }

    if ($action === 'cancel') {
        if (!in_array($order['status'], ['pending'])) {
            echo json_encode(["error" => "Only pending orders can be cancelled"]); exit;
        }
        $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?")->execute([$order_id]);
        echo json_encode(["success" => true, "message" => "Order cancelled"]);

    } elseif ($action === 'confirm_delivery') {
        if ($order['status'] !== 'shipped') {
            echo json_encode(["error" => "Order must be shipped before confirming delivery"]); exit;
        }
        $pdo->prepare("UPDATE orders SET status = 'delivered' WHERE id = ?")->execute([$order_id]);
        echo json_encode(["success" => true, "message" => "Delivery confirmed"]);

    } else {
        echo json_encode(["error" => "Invalid action"]);
    }
} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>