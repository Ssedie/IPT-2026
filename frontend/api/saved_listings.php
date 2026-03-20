<?php
// api/saved_listings.php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, DELETE");
header("Access-Control-Allow-Headers: Content-Type");
require_once '../db.php';

// Ensure saved_listings table exists
$pdo->exec("
    CREATE TABLE IF NOT EXISTS saved_listings (
        id         SERIAL PRIMARY KEY,
        buyer_id   INT REFERENCES users(id) ON DELETE CASCADE,
        listing_id INT REFERENCES listings(id) ON DELETE CASCADE,
        created_at TIMESTAMP DEFAULT NOW(),
        UNIQUE(buyer_id, listing_id)
    )
");

$method   = $_SERVER['REQUEST_METHOD'];
$buyer_id = $_GET['buyer_id'] ?? null;

if (!$buyer_id) { echo json_encode(["error" => "Unauthorized"]); exit; }

try {
    if ($method === 'GET') {
        $stmt = $pdo->prepare("
            SELECT s.id, s.listing_id, s.created_at,
                   l.product_name, l.price, l.unit, l.stock,
                   u.full_name AS farmer_name, u.city
            FROM saved_listings s
            JOIN listings l ON s.listing_id = l.id
            JOIN users u    ON l.farmer_id  = u.id
            WHERE s.buyer_id = ?
            ORDER BY s.created_at DESC
        ");
        $stmt->execute([$buyer_id]);
        echo json_encode(["saved" => $stmt->fetchAll()]);

    } elseif ($method === 'POST') {
        $data       = json_decode(file_get_contents('php://input'), true);
        $listing_id = $data['listing_id'] ?? null;
        if (!$listing_id) { echo json_encode(["error" => "Missing listing_id"]); exit; }

        $stmt = $pdo->prepare("INSERT INTO saved_listings (buyer_id, listing_id) VALUES (?,?) ON CONFLICT DO NOTHING");
        $stmt->execute([$buyer_id, $listing_id]);
        echo json_encode(["success" => true, "message" => "Saved"]);

    } elseif ($method === 'DELETE') {
        $data       = json_decode(file_get_contents('php://input'), true);
        $listing_id = $data['listing_id'] ?? null;
        if (!$listing_id) { echo json_encode(["error" => "Missing listing_id"]); exit; }

        $pdo->prepare("DELETE FROM saved_listings WHERE buyer_id = ? AND listing_id = ?")->execute([$buyer_id, $listing_id]);
        echo json_encode(["success" => true, "message" => "Removed"]);
    }
} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>