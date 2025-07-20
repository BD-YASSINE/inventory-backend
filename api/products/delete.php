<?php
require_once '../../helpers/cors.php';
handle_cors();

require_once '../../config/config.php';
require_once '../../config/db.php';
require_once '../../helpers/response.php';

// You might get user_id from authentication/session. 
// Here I assume you get user_id and id from JSON input for example.

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['id']) || !isset($input['user_id'])) {
    send_json_response([
        "success" => false,
        "message" => "Missing product id or user_id."
    ]);
    exit;
}

$productId = intval($input['id']);
$userId = intval($input['user_id']);

try {
    $db = new PDO(DB_DSN, DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Prepare delete statement with user_id check
    $stmt = $db->prepare("DELETE FROM products WHERE id = :id AND user_id = :user_id");
    $stmt->bindParam(':id', $productId, PDO::PARAM_INT);
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);

    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        send_json_response([
            "success" => true,
            "message" => "Product deleted successfully."
        ]);
    } else {
        send_json_response([
            "success" => false,
            "message" => "Product not found or you don't have permission to delete it."
        ]);
    }
} catch (PDOException $e) {
    send_json_response([
        "success" => false,
        "message" => "Database error: " . $e->getMessage()
    ]);
}
