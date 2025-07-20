<?php
require_once '../../helpers/cors.php';
handle_cors();

require_once '../../config/config.php';
require_once '../../config/db.php';
require_once '../../helpers/response.php';

// Get JSON input data (if sent via POST) or query param (GET)
$input = json_decode(file_get_contents('php://input'), true);

// Alternatively, if you prefer to get user_id via GET param:
// $userId = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;

$userId = isset($input['user_id']) ? intval($input['user_id']) : null;

if (!$userId) {
    send_json_response([
        "success" => false,
        "message" => "Missing user_id."
    ]);
    exit;
}

try {
    $db = new PDO(DB_DSN, DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Prepare and execute query to get products for this user
    $stmt = $db->prepare("SELECT id, name, description, category, created_at, user_id FROM products WHERE user_id = :user_id ORDER BY created_at DESC");
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();

    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    send_json_response([
        "success" => true,
        "products" => $products
    ]);
} catch (PDOException $e) {
    send_json_response([
        "success" => false,
        "message" => "Database error: " . $e->getMessage()
    ]);
}
