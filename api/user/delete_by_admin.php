<?php
require_once '../../helpers/cors.php';
handle_cors();

require_once '../../config/config.php';
require_once '../../config/db.php';
require_once '../../helpers/response.php';

session_start();

if (!isset($_SESSION['user_id'], $_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    send_json_response(["success" => false, "message" => "Unauthorized access."], 403);
    exit;
}

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->id)) {
    send_json_response(["success" => false, "message" => "User ID required."], 400);
    exit;
}

$userId = intval($data->id);

// لا يمكن للمدير حذف نفسه!
if ($userId === $_SESSION['user_id']) {
    send_json_response(["success" => false, "message" => "You cannot delete your own account."], 400);
    exit;
}

try {
    $db = new PDO(DB_DSN, DB_USER, DB_PASS);
    $stmt = $db->prepare("DELETE FROM users WHERE id = :id");
    $stmt->bindParam(":id", $userId);
    $stmt->execute();

    send_json_response(["success" => true, "message" => "User deleted by admin."]);
} catch (PDOException $e) {
    send_json_response(["success" => false, "message" => "Database error: " . $e->getMessage()], 500);
}
