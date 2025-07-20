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

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate required fields except user_id which is from session
if (
    !isset($input['product_id']) ||
    !isset($input['quantity']) ||
    !isset($input['price'])
) {
    send_json_response([
        "success" => false,
        "message" => "Missing required fields: product_id, quantity, price."
    ]);
    exit;
}

$product_id = intval($input['product_id']);
$quantity = intval($input['quantity']);
$price = floatval($input['price']);
$user_id = intval($_SESSION['user_id']);  // use session user id
$notes = isset($input['notes']) ? trim($input['notes']) : null;
$sold_at = isset($input['sold_at']) ? trim($input['sold_at']) : date('Y-m-d H:i:s'); // default to now

try {
    $db = new PDO(DB_DSN, DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Insert new sale
    $stmt = $db->prepare("
        INSERT INTO sales (product_id, quantity, price, notes, sold_at, user_id) 
        VALUES (:product_id, :quantity, :price, :notes, :sold_at, :user_id)
    ");

    $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
    $stmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);
    $stmt->bindParam(':price', $price);
    $stmt->bindParam(':notes', $notes);
    $stmt->bindParam(':sold_at', $sold_at);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);

    $stmt->execute();

    send_json_response([
        "success" => true,
        "message" => "Sale record added successfully.",
        "sale_id" => $db->lastInsertId()
    ]);
} catch (PDOException $e) {
    send_json_response([
        "success" => false,
        "message" => "Database error: " . $e->getMessage()
    ]);
}

