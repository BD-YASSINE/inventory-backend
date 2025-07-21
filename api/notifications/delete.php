<?php
require_once '../../helpers/cors.php';
handle_cors();

require_once '../../config/config.php';
require_once '../../config/db.php';
require_once '../../helpers/response.php';

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    send_json_response(['success' => false, 'message' => 'Unauthorized'], 401);
    exit;
}

$user_id = $_SESSION['user_id'];

// Prepare and execute delete query
$stmt = $conn->prepare("DELETE FROM notifications WHERE user_id = ?");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    if ($stmt->execute()) {
        send_json_response(['success' => true, 'message' => 'All notifications deleted.']);
    } else {
        send_json_response(['success' => false, 'message' => 'Failed to delete notifications.'], 500);
    }
    $stmt->close();
} else {
    send_json_response(['success' => false, 'message' => 'Database error.'], 500);
}

$conn->close();
