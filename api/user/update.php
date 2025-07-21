<?php
require_once '../../helpers/cors.php';
handle_cors();
require_once '../../config/config.php';
require_once '../../config/db.php';
require_once '../../helpers/response.php';

session_start();

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->id, $data->username, $data->email)) {
    send_json_response([
        "success" => false,
        "message" => "Missing required fields: id, username, email."
    ], 400);
    exit;
}

$id = intval($data->id);
$username = trim($data->username);
$email = trim($data->email);

try {
    $db = new PDO(DB_DSN, DB_USER, DB_PASS);

    $stmt = $db->prepare("UPDATE users SET username = :username, email = :email WHERE id = :id");
    $stmt->bindParam(":username", $username);
    $stmt->bindParam(":email", $email);
    $stmt->bindParam(":id", $id);
    $stmt->execute();

    send_json_response(["success" => true, "message" => "Profile updated."]);
} catch (PDOException $e) {
    send_json_response([
        "success" => false,
        "message" => "Database error: " . $e->getMessage()
    ], 500);
}



