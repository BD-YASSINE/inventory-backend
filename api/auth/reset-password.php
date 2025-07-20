<?php
require_once '../../helpers/cors.php';
handle_cors();
require_once '../../config/config.php';
require_once '../../config/db.php';
require_once '../../helpers/response.php';

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->email, $data->code, $data->password)) {
    send_json_response(["success" => false, "message" => "Email, code and new password are required."], 400);
}

$email = filter_var($data->email, FILTER_VALIDATE_EMAIL);
$code = $data->code;
$password = $data->password;

if (!$email || strlen($password) < 6) {
    send_json_response(["success" => false, "message" => "Invalid email or weak password."], 400);
}

// Check code and expiry
$stmt = $conn->prepare("SELECT id, reset_code_expiry FROM users WHERE email = ? AND reset_code = ?");
if (!$stmt) {
    send_json_response(["success" => false, "message" => "Database error: " . $conn->error], 500);
}
$stmt->bind_param("ss", $email, $code);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
    send_json_response(["success" => false, "message" => "Invalid reset code or email."], 400);
}
$stmt->bind_result($user_id, $expiry);
$stmt->fetch();
$stmt->close();

if (strtotime($expiry) < time()) {
    send_json_response(["success" => false, "message" => "Reset code expired."], 400);
}

// Hash new password and update
$password_hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $conn->prepare("UPDATE users SET password = ?, reset_code = NULL, reset_code_expiry = NULL WHERE id = ?");
if (!$stmt) {
    send_json_response(["success" => false, "message" => "Database error: " . $conn->error], 500);
}
$stmt->bind_param("si", $password_hash, $user_id);
if ($stmt->execute()) {
    send_json_response(["success" => true, "message" => "Password reset successful."]);
} else {
    send_json_response(["success" => false, "message" => "Failed to reset password."], 500);
}
$stmt->close();
$conn->close();
