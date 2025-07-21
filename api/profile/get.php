<?php
require_once '../../helpers/cors.php';
handle_cors();
require_once '../../config/config.php';
require_once '../../config/db.php';
require_once '../../helpers/response.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    send_json_response(["success" => false, "message" => "User not authenticated"]);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("SELECT id, username, email, role FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        send_json_response(["success" => false, "message" => "User not found"]);
        exit;
    }

    send_json_response([
        "success" => true,
        "data" => $user,
    ]);
} catch (Exception $e) {
    send_json_response(["success" => false, "message" => "Server error"]);
}
