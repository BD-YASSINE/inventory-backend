<?php
require_once '../../helpers/cors.php';
handle_cors();

require_once '../../config/config.php';
require_once '../../config/db.php';
require_once '../../helpers/response.php';

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['user_id'])) {
    send_json_response([
        "success" => false,
        "message" => "Missing user_id."
    ]);
    exit;
}

$user_id = intval($input['user_id']);

try {
    $db = new PDO(DB_DSN, DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $db->prepare("
        SELECT id, product_id, quantity, price, notes, sold_at, user_id
        FROM sales
        WHERE user_id = :user_id
        ORDER BY sold_at DESC
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
