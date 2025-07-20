<?php
session_start();

require_once '../../helpers/cors.php';
handle_cors();

require_once '../../config/config.php';
require_once '../../config/db.php';
require_once '../../helpers/response.php';

// Check if user is logged in via session
if (!isset($_SESSION['user_id'])) {
    send_json_response([
        "success" => false,
        "message" => "Session expired. Please log in again."
    ], 401);
    exit;
}

$user_id = intval($_SESSION['user_id']);

try {
    $db = new PDO(DB_DSN, DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // JOIN with products table to get product name
    $stmt = $db->prepare("
        SELECT 
            s.id, 
            s.product_id, 
            s.quantity, 
            s.price, 
            s.notes, 
            s.sold_at, 
            s.user_id,
            p.name AS product_name
        FROM sales s
        LEFT JOIN products p ON s.product_id = p.id
        WHERE s.user_id = :user_id
        ORDER BY s.sold_at DESC
    ");

    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();

    $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

    send_json_response([
        "success" => true,
        "sales" => $sales
    ]);
} catch (PDOException $e) {
    send_json_response([
        "success" => false,
        "message" => "Database error: " . $e->getMessage()
    ]);
}
?>
