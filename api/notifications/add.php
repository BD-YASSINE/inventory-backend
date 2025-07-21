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

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->message)) {
    send_json_response(['success' => false, 'message' => 'Notification message is required.'], 400);
    exit;
}

$user_id = $_SESSION['user_id'];
$message = $data->message;
$target_component = isset($data->targetComponent) ? $data->targetComponent : null;

try {
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, target_component) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user_id, $message, $target_component);
    $stmt->execute();

    send_json_response(['success' => true, 'message' => 'Notification added successfully.']);
} catch (Exception $e) {
    send_json_response(['success' => false, 'message' => 'Failed to add notification.'], 500);
}
