<?php
// api/farmer_listings.php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type");
require_once '../../db.php';

$method    = $_SERVER['REQUEST_METHOD'];
$farmer_id = $_GET['farmer_id'] ?? null;
if (!$farmer_id) { echo json_encode(["error" => "Unauthorized"]); exit; }

try {
    if ($method === 'GET') {
        $status = $_GET['status'] ?? 'active';
        $where  = $status === 'all' ? "farmer_id = ?" : "farmer_id = ? AND status = ?";
        $params = $status === 'all' ? [$farmer_id] : [$farmer_id, $status];

        $stmt = $pdo->prepare("
            SELECT l.*,
                   (SELECT COUNT(*) FROM orders o WHERE o.listing_id = l.id) AS total_orders
            FROM listings l
            WHERE $where
            ORDER BY l.created_at DESC
        ");
        $stmt->execute($params);
        echo json_encode(["listings" => $stmt->fetchAll()]);

    } elseif ($method === 'POST') {
        // Create new listing
        $data         = json_decode(file_get_contents('php://input'), true);
        $product_name = trim($data['product_name'] ?? '');
        $price        = (float)($data['price']  ?? 0);
        $stock        = (float)($data['stock']  ?? 0);
        $unit         = trim($data['unit']       ?? 'kg');

        if (!$product_name || $price <= 0 || $stock <= 0) {
            echo json_encode(["error" => "All fields are required"]); exit;
        }

        $stmt = $pdo->prepare("
            INSERT INTO listings (farmer_id, product_name, price, stock, unit, status)
            VALUES (?, ?, ?, ?, ?, 'active') RETURNING id
        ");
        $stmt->execute([$farmer_id, $product_name, $price, $stock, $unit]);
        $id = $stmt->fetch()['id'];
        echo json_encode(["success" => true, "id" => $id, "message" => "Listing created"]);

    } elseif ($method === 'PUT') {
        // Update listing
        $data         = json_decode(file_get_contents('php://input'), true);
        $listing_id   = $data['id']           ?? null;
        $product_name = trim($data['product_name'] ?? '');
        $price        = (float)($data['price'] ?? 0);
        $stock        = (float)($data['stock'] ?? 0);
        $unit         = trim($data['unit']     ?? 'kg');

        if (!$listing_id || !$product_name || $price <= 0) {
            echo json_encode(["error" => "Invalid data"]); exit;
        }

        // Verify ownership
        $stmt = $pdo->prepare("SELECT id FROM listings WHERE id = ? AND farmer_id = ?");
        $stmt->execute([$listing_id, $farmer_id]);
        if (!$stmt->fetch()) { echo json_encode(["error" => "Listing not found"]); exit; }

        $pdo->prepare("
            UPDATE listings SET product_name=?, price=?, stock=?, unit=?, updated_at=NOW()
            WHERE id=? AND farmer_id=?
        ")->execute([$product_name, $price, $stock, $unit, $listing_id, $farmer_id]);

        echo json_encode(["success" => true, "message" => "Listing updated"]);

    } elseif ($method === 'DELETE') {
        $data       = json_decode(file_get_contents('php://input'), true);
        $listing_id = $data['id'] ?? null;
        if (!$listing_id) { echo json_encode(["error" => "Missing id"]); exit; }

        // Soft delete — mark as removed
        $stmt = $pdo->prepare("SELECT id FROM listings WHERE id = ? AND farmer_id = ?");
        $stmt->execute([$listing_id, $farmer_id]);
        if (!$stmt->fetch()) { echo json_encode(["error" => "Listing not found"]); exit; }

        $pdo->prepare("UPDATE listings SET status = 'removed' WHERE id = ? AND farmer_id = ?")
            ->execute([$listing_id, $farmer_id]);
        echo json_encode(["success" => true, "message" => "Listing removed"]);
    }
} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>