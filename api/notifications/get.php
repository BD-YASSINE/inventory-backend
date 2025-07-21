<?php
require_once '../../helpers/cors.php';
handle_cors();
require_once '../../config/config.php';
require_once '../../config/db.php';
require_once '../../helpers/response.php';

session_start();

// Ensure user is authenticated
if (!isset($_SESSION['user_id'])) {
    send_json_response(['success' => false, 'message' => 'Unauthorized'], 401);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    $stmt = $conn->prepare("SELECT id, message, target_component AS targetComponent, created_at AS date FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $notifications = [];
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }

    send_json_response($notifications);
} catch (Exception $e) {
    send_json_response(['success' => false, 'message' => 'Error fetching notifications.'], 500);
}
?>


