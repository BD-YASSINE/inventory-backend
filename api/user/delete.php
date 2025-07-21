<?php
require_once '../../helpers/cors.php';
handle_cors();

require_once '../../config/config.php';
require_once '../../config/db.php';
require_once '../../helpers/response.php';

session_start();

$data = json_decode(file_get_contents("php://input"));

if (!isset($_SESSION['user_id'], $data->password)) {
    send_json_response(["success" => false, "message" => "Missing session or password."], 400);
    exit;
}

$userId = $_SESSION['user_id'];
$password = $data->password;

try {
    $db = new PDO(DB_DSN, DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get user hashed password
    $stmt = $db->prepare("SELECT password FROM users WHERE id = :id");
    $stmt->bindParam(":id", $userId, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        send_json_response(["success" => false, "message" => "User not found."], 404);
        exit;
    }

    if (!password_verify($password, $row['password'])) {
        send_json_response(["success" => false, "message" => "Incorrect password."], 403);
        exit;
    }

    // Delete user
    $stmt = $db->prepare("DELETE FROM users WHERE id = :id");
    $stmt->bindParam(":id", $userId, PDO::PARAM_INT);
    $stmt->execute();

    session_destroy();

    send_json_response(["success" => true, "message" => "Account deleted."]);

} catch (PDOException $e) {
    send_json_response(["success" => false, "message" => "Database error: " . $e->getMessage()], 500);
}


