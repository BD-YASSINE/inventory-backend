<?php
require_once '../../helpers/cors.php';
handle_cors();
require_once '../../config/config.php';
require_once '../../config/db.php';
require_once '../../helpers/response.php';

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->email, $data->code, $data->password)) {
    send_json_response(["success" => false, "message" => "Email, code and new password are required."], 400);
    exit;
}

$email = filter_var($data->email, FILTER_VALIDATE_EMAIL);
$code = $data->code;
$password = $data->password;

if (!$email || strlen($password) < 6) {
    send_json_response(["success" => false, "message" => "Invalid email or weak password."], 400);
    exit;
}

try {
    $db = new PDO(DB_DSN, DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if reset code and email are valid
    $stmt = $db->prepare("SELECT id, reset_code_expiry FROM users WHERE email = :email AND reset_code = :code");
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':code', $code);
    $stmt->execute();

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        send_json_response(["success" => false, "message" => "Invalid reset code or email."], 400);
        exit;
    }

    if (strtotime($user['reset_code_expiry']) < time()) {
        send_json_response(["success" => false, "message" => "Reset code expired."], 400);
        exit;
    }

    // Hash new password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Update password and clear reset code fields
    $updateStmt = $db->prepare("UPDATE users SET password = :password, reset_code = NULL, reset_code_expiry = NULL WHERE id = :id");
    $updateStmt->bindParam(':password', $password_hash);
    $updateStmt->bindParam(':id', $user['id']);

    if ($updateStmt->execute()) {
        send_json_response(["success" => true, "message" => "Password reset successful."]);
    } else {
        send_json_response(["success" => false, "message" => "Failed to reset password."], 500);
    }
} catch (PDOException $e) {
    send_json_response(["success" => false, "message" => "Database error: " . $e->getMessage()], 500);
}

