<?php
session_start();  // start session at top

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

$userId = intval($_SESSION['user_id']);

try {
    $db = new PDO(DB_DSN, DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch products for the logged-in user
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
