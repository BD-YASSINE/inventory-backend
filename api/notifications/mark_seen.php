<?php
require_once '../../helpers/cors.php';
handle_cors();
require_once '../../config/config.php';
require_once '../../config/db.php';
require_once '../../helpers/response.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    send_json_response(['success' => false, 'message' => 'Unauthorized'], 401);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    $stmt = $conn->prepare("UPDATE notifications SET seen = 1 WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    send_json_response(['success' => true]);
} catch (Exception $e) {
    send_json_response(['success' => false, 'message' => 'Database error'], 500);
}
