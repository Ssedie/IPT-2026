<?php
// api/notifications.php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");
require_once '../../db.php';

$pdo->exec("
    CREATE TABLE IF NOT EXISTS notifications (
        id         SERIAL PRIMARY KEY,
        user_id    INT REFERENCES users(id) ON DELETE CASCADE,
        type       VARCHAR(50),
        title      VARCHAR(200),
        body       TEXT,
        is_read    BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT NOW()
    )
");

$method  = $_SERVER['REQUEST_METHOD'];
$user_id = $_GET['user_id'] ?? null;

if (!$user_id) { echo json_encode(["error" => "Unauthorized"]); exit; }

try {
    if ($method === 'GET') {
        $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
        $stmt->execute([$user_id]);
        $notifs = $stmt->fetchAll();

        $unread = array_filter($notifs, fn($n) => !$n['is_read']);
        echo json_encode(["notifications" => $notifs, "unread_count" => count($unread)]);

    } elseif ($method === 'POST') {
        // Mark all as read
        $pdo->prepare("UPDATE notifications SET is_read = TRUE WHERE user_id = ?")->execute([$user_id]);
        echo json_encode(["success" => true]);
    }
} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>