<?php
session_start();

require_once '../../helpers/cors.php';
handle_cors();

require_once '../../config/config.php';
require_once '../../config/db.php';
require_once '../../helpers/response.php';

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['id']) || !isset($input['quantity'])) {
    send_json_response([
        "success" => false,
        "message" => "Missing required fields: id, quantity."
    ]);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    send_json_response([
        "success" => false,
        "message" => "Session expired. Please log in again."
    ], 401);
    exit;
}

$id = intval($input['id']);
$user_id = intval($_SESSION['user_id']);
$quantity = intval($input['quantity']);
$notes = isset($input['notes']) ? trim($input['notes']) : null;
$updated_at = date('Y-m-d H:i:s');

try {
    $db = new PDO(DB_DSN, DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $db->prepare("
        UPDATE stock
        SET quantity = :quantity, notes = :notes, updated_at = :updated_at
        WHERE id = :id AND user_id = :user_id
    ");

    $stmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);

    if ($notes === null || $notes === '') {
        $stmt->bindValue(':notes', null, PDO::PARAM_NULL);
    } else {
        $stmt->bindParam(':notes', $notes, PDO::PARAM_STR);
    }

    $stmt->bindParam(':updated_at', $updated_at);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);

    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        send_json_response([
            "success" => true,
            "message" => "Stock updated successfully."
        ]);
    } else {
        send_json_response([
            "success" => false,
            "message" => "Stock not found or you don't have permission to update it."
        ]);
    }
} catch (PDOException $e) {
    send_json_response([
        "success" => false,
        "message" => "Database error: " . $e->getMessage()
    ]);
}


