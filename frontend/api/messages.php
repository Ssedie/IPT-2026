<?php
// api/messages.php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");
require_once '../db.php';

// Create messages table if not exists
$pdo->exec("
    CREATE TABLE IF NOT EXISTS messages (
        id          SERIAL PRIMARY KEY,
        sender_id   INT REFERENCES users(id),
        receiver_id INT REFERENCES users(id),
        message     TEXT NOT NULL,
        is_read     BOOLEAN DEFAULT FALSE,
        created_at  TIMESTAMP DEFAULT NOW()
    )
");

$method  = $_SERVER['REQUEST_METHOD'];
$user_id = $_GET['user_id'] ?? null;

if (!$user_id) { echo json_encode(["error" => "Unauthorized"]); exit; }

try {
    if ($method === 'GET') {
        $with = $_GET['with'] ?? null;

        if ($with) {
            // Get conversation with a specific user
            $stmt = $pdo->prepare("
                SELECT m.*, u.full_name AS sender_name
                FROM messages m
                JOIN users u ON m.sender_id = u.id
                WHERE (m.sender_id = ? AND m.receiver_id = ?)
                   OR (m.sender_id = ? AND m.receiver_id = ?)
                ORDER BY m.created_at ASC
            ");
            $stmt->execute([$user_id, $with, $with, $user_id]);
            $messages = $stmt->fetchAll();

            // Mark as read
            $pdo->prepare("UPDATE messages SET is_read = TRUE WHERE receiver_id = ? AND sender_id = ?")
                ->execute([$user_id, $with]);

            echo json_encode(["messages" => $messages]);
        } else {
            // Get conversation list (latest message per contact)
            $stmt = $pdo->prepare("
                SELECT DISTINCT ON (contact_id)
                    contact_id,
                    u.full_name AS contact_name,
                    u.role AS contact_role,
                    m.message AS last_message,
                    m.created_at,
                    m.is_read,
                    m.sender_id
                FROM (
                    SELECT
                        CASE WHEN sender_id = ? THEN receiver_id ELSE sender_id END AS contact_id,
                        message, created_at, is_read, sender_id
                    FROM messages
                    WHERE sender_id = ? OR receiver_id = ?
                ) m
                JOIN users u ON u.id = m.contact_id
                ORDER BY contact_id, m.created_at DESC
            ");
            $stmt->execute([$user_id, $user_id, $user_id]);
            echo json_encode(["conversations" => $stmt->fetchAll()]);
        }

    } elseif ($method === 'POST') {
        $data        = json_decode(file_get_contents('php://input'), true);
        $receiver_id = $data['receiver_id'] ?? null;
        $message     = trim($data['message'] ?? '');

        if (!$receiver_id || !$message) { echo json_encode(["error" => "Missing fields"]); exit; }

        $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?,?,?)")
            ->execute([$user_id, $receiver_id, $message]);

        echo json_encode(["success" => true, "message" => "Sent"]);
    }
} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>