<?php
require_once '../../helpers/cors.php';
handle_cors();
require_once '../../config/config.php';
require_once '../../config/db.php';
require_once '../../helpers/response.php';

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->username, $data->email, $data->password)) {
    send_json_response(["success" => false, "message" => "Missing fields: username, email, and password are required."], 400);
}

$username = htmlspecialchars(trim($data->username));
$email = filter_var($data->email, FILTER_VALIDATE_EMAIL);
$password = $data->password;

if (!$email) {
    send_json_response(["success" => false, "message" => "Invalid email format."], 400);
}

if (strlen($password) < 6) {
    send_json_response(["success" => false, "message" => "Password must be at least 6 characters."], 400);
}

// Check if email already registered
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
if (!$stmt) {
    send_json_response(["success" => false, "message" => "Database error: " . $conn->error], 500);
}
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $stmt->close();
    send_json_response(["success" => false, "message" => "Email already registered."], 409);
}
$stmt->close();

// Hash password and insert
$password_hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
if (!$stmt) {
    send_json_response(["success" => false, "message" => "Database error: " . $conn->error], 500);
}
$stmt->bind_param("sss", $username, $email, $password_hash);
if ($stmt->execute()) {
    send_json_response(["success" => true, "message" => "User registered successfully."]);
} else {
    send_json_response(["success" => false, "message" => "Failed to register user."], 500);
}
$stmt->close();
$conn->close();
