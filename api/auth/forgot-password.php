<?php
require_once '../../helpers/cors.php';
handle_cors();
require_once '../../config/config.php';
require_once '../../config/db.php';
require_once '../../helpers/response.php';

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->email)) {
    send_json_response(["success" => false, "message" => "Email required."], 400);
}

$email = filter_var($data->email, FILTER_VALIDATE_EMAIL);
if (!$email) {
    send_json_response(["success" => false, "message" => "Invalid email."], 400);
}

// Check if user exists
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
if (!$stmt) {
    send_json_response(["success" => false, "message" => "Database error: " . $conn->error], 500);
}
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
    $stmt->close();
    send_json_response(["success" => false, "message" => "Email not registered."], 404);
}
$stmt->bind_result($user_id);
$stmt->fetch();
$stmt->close();

// Generate reset code and expiry (15 minutes)
$reset_code = bin2hex(random_bytes(16));
$expiry = date('Y-m-d H:i:s', time() + 900);

$stmt = $conn->prepare("UPDATE users SET reset_code = ?, reset_code_expiry = ? WHERE id = ?");
if (!$stmt) {
    send_json_response(["success" => false, "message" => "Database error: " . $conn->error], 500);
}
$stmt->bind_param("ssi", $reset_code, $expiry, $user_id);
if ($stmt->execute()) {
    // NOTE: Here you should send the reset code via email (not implemented)
    send_json_response(["success" => true, "message" => "Reset code generated. Please check your email (not implemented)."]);
} else {
    send_json_response(["success" => false, "message" => "Failed to set reset code."], 500);
}
$stmt->close();
$conn->close();
