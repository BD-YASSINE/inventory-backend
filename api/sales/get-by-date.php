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

// Validate required fields
if (!isset($input['start_date']) || !isset($input['end_date'])) {
    send_json_response([
        "success" => false,
        "message" => "Missing required fields: start_date and end_date."
    ]);
    exit;
}

$user_id = intval($_SESSION['user_id']);  // use session user_id
$start_date = $input['start_date']; // 'YYYY-MM-DD' or full datetime
$end_date = $input['end_date'];

try {
    $db = new PDO(DB_DSN, DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $db->prepare("
        SELECT id, product_id, quantity, price, notes, sold_at, user_id
        FROM sales
        WHERE user_id = :user_id
          AND sold_at BETWEEN :start_date AND :end_date
        ORDER BY sold_at DESC
    ");

    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindParam(':start_date', $start_date);
    $stmt->bindParam(':end_date', $end_date);

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
