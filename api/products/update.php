<?php
session_start();

require_once '../../helpers/cors.php';
handle_cors();

require_once '../../config/config.php';
require_once '../../config/db.php';
require_once '../../helpers/response.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    send_json_response([
        "success" => false,
        "message" => "Session expired. Please log in again."
    ], 401);
    exit;
}

// Get input JSON data
$input = json_decode(file_get_contents('php://input'), true);

// Validate required fields (no user_id from client now)
if (
    !isset($input['id']) ||
    !isset($input['name']) ||
    !isset($input['description']) ||
    !isset($input['category'])
) {
    send_json_response([
        "success" => false,
        "message" => "Missing required fields: id, name, description, category."
    ]);
    exit;
}

$id = intval($input['id']);
$user_id = intval($_SESSION['user_id']); // use user id from session
$name = trim($input['name']);
$description = trim($input['description']);
$category = trim($input['category']);

try {
    $db = new PDO(DB_DSN, DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Update only if product belongs to this user
    $stmt = $db->prepare("
        UPDATE products 
        SET name = :name, description = :description, category = :category 
        WHERE id = :id AND user_id = :user_id
    ");

    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':category', $category);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);

    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        send_json_response([
            "success" => true,
            "message" => "Product updated successfully."
        ]);
    } else {
        send_json_response([
            "success" => false,
            "message" => "Product not found or you don't have permission to update it."
        ]);
    }
} catch (PDOException $e) {
    send_json_response([
        "success" => false,
        "message" => "Database error: " . $e->getMessage()
    ]);
}
