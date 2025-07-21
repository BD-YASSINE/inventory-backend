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

// Get user and verify password
$stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    send_json_response(["success" => false, "message" => "User not found."], 404);
    exit;
}

$row = $result->fetch_assoc();

if (!password_verify($password, $row['password'])) {
    send_json_response(["success" => false, "message" => "Incorrect password."], 403);
    exit;
}

// Delete user
$stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();

session_destroy();
send_json_response(["success" => true, "message" => "Account deleted."]);


