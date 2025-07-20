<?php
require_once '../../helpers/cors.php';
handle_cors();

require_once '../../config/config.php';
require_once '../../config/db.php';
require_once '../../helpers/response.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['id']) || !isset($input['old_password']) || !isset($input['new_password'])) {
    send_json_response([
        "success" => false,
        "message" => "Missing required fields: id, old_password, new_password."
    ]);
    exit;
}

$id = intval($input['id']);
$old_password = $input['old_password'];
$new_password = password_hash($input['new_password'], PASSWORD_DEFAULT);

try {
    $db = new PDO(DB_DSN, DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $db->prepare("SELECT password FROM users WHERE id = :id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($old_password, $user['password'])) {
        send_json_response([
            "success" => false,
            "message" => "Old password is incorrect."
        ]);
        exit;
    }

    $updateStmt = $db->prepare("UPDATE users SET password = :new_password WHERE id = :id");
    $updateStmt->bindParam(':new_password', $new_password);
    $updateStmt->bindParam(':id', $id, PDO::PARAM_INT);
    $updateStmt->execute();

    send_json_response(["success" => true, "message" => "Password updated successfully."]);
} catch (PDOException $e) {
    send_json_response([
        "success" => false,
        "message" => "Database error: " . $e->getMessage()
    ]);
}
