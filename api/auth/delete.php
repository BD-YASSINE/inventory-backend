<?php
require_once '../../helpers/cors.php';
handle_cors();
require_once '../../config/config.php';
require_once '../../config/db.php';
require_once '../../helpers/response.php';

// Get input
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['user_id'], $data['password'])) {
    send_json_response([
        "success" => false,
        "message" => "User ID and password required."
    ], 400);
    exit;
}

$user_id = intval($data['user_id']);
$password = $data['password'];

try {
    $db = new PDO(DB_DSN, DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Step 1: Fetch hashed password from DB
    $stmt = $db->prepare("SELECT password FROM users WHERE id = :user_id");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        send_json_response([
            "success" => false,
            "message" => "User not found."
        ], 404);
        exit;
    }

    // Step 2: Verify password
    if (!password_verify($password, $user['password'])) {
        send_json_response([
            "success" => false,
            "message" => "Incorrect password."
        ], 401);
        exit;
    }

    // Step 3: Delete user
    $delete = $db->prepare("DELETE FROM users WHERE id = :user_id");
    $delete->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $delete->execute();

    if ($delete->rowCount() > 0) {
        send_json_response([
            "success" => true,
            "message" => "Account deleted successfully."
        ]);
    } else {
        send_json_response([
            "success" => false,
            "message" => "Failed to delete account."
        ], 500);
    }

} catch (PDOException $e) {
    send_json_response([
        "success" => false,
        "message" => "Database error: " . $e->getMessage()
    ], 500);
}
