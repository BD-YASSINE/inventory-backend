<?php
require_once '../../helpers/cors.php';
handle_cors();

require_once '../../config/config.php';
require_once '../../config/db.php';
require_once '../../helpers/response.php';

// Get input JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['id']) || !isset($input['user_id'])) {
    send_json_response([
        "success" => false,
        "message" => "Missing id or user_id."
    ]);
    exit;
}

$id = intval($input['id']);
$user_id = intval($input['user_id']);

try {
    $db = new PDO(DB_DSN, DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Delete only if sale belongs to user
    $stmt = $db->prepare("DELETE FROM sales WHERE id = :id AND user_id = :user_id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);

    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        send_json_response([
            "success" => true,
            "message" => "Sale record deleted successfully."
        ]);
    } else {
        send_json_response([
            "success" => false,
            "message" => "Sale record not found or you don't have permission to delete it."
        ]);
    }
} catch (PDOException $e) {
    send_json_response([
        "success" => false,
        "message" => "Database error: " . $e->getMessage()
    ]);
}
