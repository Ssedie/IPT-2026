<?php
// api/place_order.php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
require_once '../db.php';

$data       = json_decode(file_get_contents('php://input'), true);
$buyer_id   = $data['buyer_id']   ?? null;
$listing_id = $data['listing_id'] ?? null;
$quantity   = (float)($data['quantity'] ?? 0);

if (!$buyer_id || !$listing_id || $quantity <= 0) {
    echo json_encode(["error" => "Missing parameters"]); exit;
}

try {
    // Get listing
    $stmt = $pdo->prepare("SELECT * FROM listings WHERE id = ? AND status = 'active'");
    $stmt->execute([$listing_id]);
    $listing = $stmt->fetch();

    if (!$listing) { echo json_encode(["error" => "Listing not found or inactive"]); exit; }
    if ($listing['stock'] < $quantity) { echo json_encode(["error" => "Insufficient stock"]); exit; }
    if ($listing['farmer_id'] == $buyer_id) { echo json_encode(["error" => "Cannot order your own listing"]); exit; }

    $total = $listing['price'] * $quantity;

    $pdo->beginTransaction();

    // Insert order
    $stmt = $pdo->prepare("
        INSERT INTO orders (buyer_id, seller_id, listing_id, total_amount, status)
        VALUES (?, ?, ?, ?, 'pending') RETURNING id
    ");
    $stmt->execute([$buyer_id, $listing['farmer_id'], $listing_id, $total]);
    $order_id = $stmt->fetch()['id'];

    // Deduct stock
    $pdo->prepare("UPDATE listings SET stock = stock - ? WHERE id = ?")->execute([$quantity, $listing_id]);

    // Insert transaction
    $pdo->prepare("
        INSERT INTO transactions (user_id, order_id, type, amount, status)
        VALUES (?, ?, 'payment', ?, 'completed')
    ")->execute([$buyer_id, $order_id, $total]);

    $pdo->commit();

    echo json_encode(["success" => true, "order_id" => $order_id, "total" => $total]);
} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(["error" => $e->getMessage()]);
}
?>