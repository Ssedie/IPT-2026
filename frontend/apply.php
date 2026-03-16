<?php
// frontend/apply.php
// Handles farmer application form submission (multipart/form-data for file uploads)

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");

require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed"]);
    exit;
}

// ── Auth: user_id comes from POST (sent by JS from localStorage) ──────────
$user_id = intval($_POST['user_id'] ?? 0);
if (!$user_id) {
    http_response_code(401);
    echo json_encode(["error" => "Not authenticated"]);
    exit;
}

// ── Verify user exists and is a buyer ─────────────────────────────────────
$userStmt = $pdo->prepare("SELECT id, role, status FROM users WHERE id = ?");
$userStmt->execute([$user_id]);
$user = $userStmt->fetch();

if (!$user) {
    http_response_code(404);
    echo json_encode(["error" => "User not found"]);
    exit;
}

// ── Check for existing pending application ────────────────────────────────
$pendingStmt = $pdo->prepare("
    SELECT id FROM farmer_applications
    WHERE user_id = ? AND status = 'pending'
");
$pendingStmt->execute([$user_id]);
if ($pendingStmt->fetch()) {
    http_response_code(409);
    echo json_encode(["error" => "You already have a pending application."]);
    exit;
}

// ── Check 1-day cooldown for rejected applications ────────────────────────
$cooldownStmt = $pdo->prepare("
    SELECT rejected_at FROM farmer_applications
    WHERE user_id = ? AND status = 'rejected'
    ORDER BY rejected_at DESC
    LIMIT 1
");
$cooldownStmt->execute([$user_id]);
$lastRejection = $cooldownStmt->fetch();

if ($lastRejection && $lastRejection['rejected_at']) {
    $rejectedAt  = new DateTime($lastRejection['rejected_at']);
    $now         = new DateTime();
    $diff        = $now->diff($rejectedAt);
    $hoursWaited = ($diff->days * 24) + $diff->h;

    if ($hoursWaited < 24) {
        $hoursLeft = 24 - $hoursWaited;
        http_response_code(429);
        echo json_encode([
            "error" => "You must wait {$hoursLeft} more hour(s) before reapplying."
        ]);
        exit;
    }
}

// ── Validate required fields ──────────────────────────────────────────────
$required = ['farm_name', 'farm_size', 'farm_address', 'barangay', 'city', 'province', 'crops'];
foreach ($required as $field) {
    if (empty(trim($_POST[$field] ?? ''))) {
        http_response_code(400);
        echo json_encode(["error" => "Missing required field: $field"]);
        exit;
    }
}

// Required documents
$requiredDocs = ['doc_government_id', 'doc_land_title', 'doc_barangay_cert', 'doc_dar_cert'];
foreach ($requiredDocs as $doc) {
    if (empty($_FILES[$doc]['name'])) {
        http_response_code(400);
        echo json_encode(["error" => "Missing required document: $doc"]);
        exit;
    }
}

// ── File upload helper ────────────────────────────────────────────────────
function uploadFile($fileKey, $subfolder, $allowedTypes = ['image/jpeg','image/png','application/pdf']) {
    if (empty($_FILES[$fileKey]['name'])) return null;

    $file     = $_FILES[$fileKey];
    $maxSize  = 5 * 1024 * 1024; // 5MB

    if ($file['size'] > $maxSize) {
        throw new Exception("File $fileKey exceeds 5MB limit.");
    }

    $finfo    = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedTypes)) {
        throw new Exception("File $fileKey has an invalid type ($mimeType).");
    }

    $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid($fileKey . '_', true) . '.' . $ext;
    $uploadDir= __DIR__ . "/uploads/$subfolder/";

    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $dest = $uploadDir . $filename;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        throw new Exception("Failed to save file $fileKey.");
    }

    return "uploads/$subfolder/$filename"; // relative path stored in DB
}

// ── Upload all files ──────────────────────────────────────────────────────
try {
    $docTypes = ['image/jpeg', 'image/png', 'application/pdf'];
    $imgTypes = ['image/jpeg', 'image/png', 'image/webp'];

    $doc_government_id = uploadFile('doc_government_id', 'docs', $docTypes);
    $doc_land_title    = uploadFile('doc_land_title',    'docs', $docTypes);
    $doc_barangay_cert = uploadFile('doc_barangay_cert', 'docs', $docTypes);
    $doc_dar_cert      = uploadFile('doc_dar_cert',      'docs', $docTypes);

    $photo_1 = uploadFile('photo_1', 'farm_photos', $imgTypes);
    $photo_2 = uploadFile('photo_2', 'farm_photos', $imgTypes);
    $photo_3 = uploadFile('photo_3', 'farm_photos', $imgTypes);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(["error" => $e->getMessage()]);
    exit;
}

// ── Insert application ────────────────────────────────────────────────────
try {
    $stmt = $pdo->prepare("
        INSERT INTO farmer_applications (
            user_id, farm_name, farm_size, farm_address,
            barangay, city, province, crops,
            doc_government_id, doc_land_title, doc_barangay_cert, doc_dar_cert,
            photo_1, photo_2, photo_3,
            status, created_at, updated_at
        ) VALUES (
            :user_id, :farm_name, :farm_size, :farm_address,
            :barangay, :city, :province, :crops,
            :doc_gov_id, :doc_land, :doc_brgy, :doc_dar,
            :photo_1, :photo_2, :photo_3,
            'pending', NOW(), NOW()
        )
    ");

    $stmt->execute([
        ':user_id'    => $user_id,
        ':farm_name'  => trim($_POST['farm_name']),
        ':farm_size'  => floatval($_POST['farm_size']),
        ':farm_address'=> trim($_POST['farm_address']),
        ':barangay'   => trim($_POST['barangay']),
        ':city'       => trim($_POST['city']),
        ':province'   => trim($_POST['province']),
        ':crops'      => trim($_POST['crops']),
        ':doc_gov_id' => $doc_government_id,
        ':doc_land'   => $doc_land_title,
        ':doc_brgy'   => $doc_barangay_cert,
        ':doc_dar'    => $doc_dar_cert,
        ':photo_1'    => $photo_1,
        ':photo_2'    => $photo_2,
        ':photo_3'    => $photo_3,
    ]);

    echo json_encode([
        "success" => true,
        "message" => "Application submitted successfully! We'll review it shortly."
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
}
?>