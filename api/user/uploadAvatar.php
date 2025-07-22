<?php
// Allow CORS from your frontend origin and enable credentials (cookies)
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight OPTIONS request and exit immediately
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../../config/db.php'; // provides $conn
require_once '../../helpers/response.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    send_json_response(["success" => false, "message" => "Not authenticated"], 401);
    exit;
}

$userId = $_SESSION['user_id'];

if (!isset($_FILES['avatar'])) {
    send_json_response(["success" => false, "message" => "No file uploaded"], 400);
    exit;
}

$file = $_FILES['avatar'];

// Define the upload directory relative to this PHP file's location
$targetDir = __DIR__ . "/../../uploads/avatars/";

// Create directory if it doesn't exist
if (!is_dir($targetDir)) {
    if (!mkdir($targetDir, 0755, true)) {
        send_json_response(["success" => false, "message" => "Failed to create upload directory"], 500);
        exit;
    }
}

$filename = uniqid() . "_" . basename($file["name"]);
$targetFile = $targetDir . $filename;

if (!move_uploaded_file($file["tmp_name"], $targetFile)) {
    send_json_response(["success" => false, "message" => "Failed to save file"], 500);
    exit;
}

// URL path to store in DB (to be used in frontend src)
$relativePath = "/uploads/avatars/" . $filename;

$stmt = $conn->prepare("UPDATE users SET avatar_url = ? WHERE id = ?");
if (!$stmt) {
    send_json_response(["success" => false, "message" => "Prepare failed: " . $conn->error], 500);
    exit;
}

$stmt->bind_param("si", $relativePath, $userId);

if (!$stmt->execute()) {
    send_json_response(["success" => false, "message" => "Execute failed: " . $stmt->error], 500);
    exit;
}

$stmt->close();

send_json_response(["success" => true, "avatar_url" => $relativePath]);
