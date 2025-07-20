<?php
session_start();

require_once '../../helpers/cors.php';
handle_cors();

require_once '../../config/config.php';
require_once '../../config/db.php';
require_once '../../helpers/response.php';

// Check session user_id
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

    $stmt = $db->prepare("
        SELECT 
            s.id, 
            s.product_id, 
            s.quantity, 
            s.notes, 
            s.updated_at as date,
            p.name AS product_name 
        FROM stock s
        JOIN products p ON s.product_id = p.id
        WHERE s.user_id = :user_id
        ORDER BY s.updated_at DESC
    ");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();

    $stocks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    send_json_response([
        "success" => true,
        "stock" => $stocks  // note key "stock" not "stocks"
    ]);
} catch (PDOException $e) {
    send_json_response([
        "success" => false,
        "message" => "Database error: " . $e->getMessage()
    ]);
}

