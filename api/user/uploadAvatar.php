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

require_once '../../config/db.php'; // adjust as needed
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
$targetDir = "../../uploads/avatars/";
$filename = uniqid() . "_" . basename($file["name"]);
$targetFile = $targetDir . $filename;

if (!move_uploaded_file($file["tmp_name"], $targetFile)) {
    send_json_response(["success" => false, "message" => "Failed to save file"], 500);
    exit;
}

// Save to DB
$relativePath = "/uploads/avatars/" . $filename; // fix path: added /avatars/
$stmt = $pdo->prepare("UPDATE users SET avatar_url = ? WHERE id = ?");
$stmt->execute([$relativePath, $userId]);

send_json_response(["success" => true, "avatar_url" => $relativePath]);
