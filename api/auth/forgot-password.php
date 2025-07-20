<?php
require_once '../../helpers/cors.php';
handle_cors();
require_once '../../config/config.php';
require_once '../../config/db.php';
require_once '../../helpers/response.php';

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->email)) {
    send_json_response(["success" => false, "message" => "Email required."], 400);
    exit;
}

$email = filter_var($data->email, FILTER_VALIDATE_EMAIL);
if (!$email) {
    send_json_response(["success" => false, "message" => "Invalid email."], 400);
    exit;
}

try {
    $db = new PDO(DB_DSN, DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if user exists
    $stmt = $db->prepare("SELECT id FROM users WHERE email = :email");
    $stmt->bindParam(':email', $email);
    $stmt->execute();

    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        send_json_response(["success" => false, "message" => "Email not registered."], 404);
        exit;
    }

    // Generate reset code and expiry (15 minutes from now)
    $reset_code = bin2hex(random_bytes(16));
    $expiry = date('Y-m-d H:i:s', time() + 900);

    // Update user record with reset code and expiry
    $updateStmt = $db->prepare("UPDATE users SET reset_code = :reset_code, reset_code_expiry = :expiry WHERE id = :id");
    $updateStmt->bindParam(':reset_code', $reset_code);
    $updateStmt->bindParam(':expiry', $expiry);
    $updateStmt->bindParam(':id', $user['id']);

    if ($updateStmt->execute()) {
        // TODO: Send $reset_code to user email here (email sending not implemented)
        send_json_response([
            "success" => true,
            "message" => "Reset code generated. Please check your email (not implemented)."
        ]);
    } else {
        send_json_response(["success" => false, "message" => "Failed to set reset code."], 500);
    }
} catch (PDOException $e) {
    send_json_response(["success" => false, "message" => "Database error: " . $e->getMessage()], 500);
}

