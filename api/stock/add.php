<?php
require_once '../../helpers/cors.php';
handle_cors();

require_once '../../config/config.php';
require_once '../../config/db.php';
require_once '../../helpers/response.php';

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (
    !isset($input['product_id']) ||
    !isset($input['quantity']) ||
    !isset($input['user_id'])
) {
    send_json_response([
        "success" => false,
        "message" => "Missing required fields: product_id, quantity, user_id."
    ]);
    exit;
}

$product_id = intval($input['product_id']);
$quantity = intval($input['quantity']);
$user_id = intval($input['user_id']);
$notes = isset($input['notes']) ? trim($input['notes']) : null;
$updated_at = date('Y-m-d H:i:s'); // current datetime

try {
    $db = new PDO(DB_DSN, DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $db->prepare("
        INSERT INTO stock (product_id, quantity, notes, updated_at, user_id)
        VALUES (:product_id, :quantity, :notes, :updated_at, :user_id)
    ");

    $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
    $stmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);
    $stmt->bindParam(':notes', $notes);
    $stmt->bindParam(':updated_at', $updated_at);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);

    $stmt->execute();

    send_json_response([
        "success" => true,
        "message" => "Stock record added successfully.",
        "stock_id" => $db->lastInsertId()
    ]);
} catch (PDOException $e) {
    send_json_response([
        "success" => false,
        "message" => "Database error: " . $e->getMessage()
    ]);
}
