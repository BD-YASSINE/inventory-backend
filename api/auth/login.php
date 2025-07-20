<?php
require_once '../../helpers/cors.php';
handle_cors();
require_once '../../config/config.php';
require_once '../../config/db.php';
require_once '../../helpers/response.php';

session_start();

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->email, $data->password)) {
    send_json_response(["success" => false, "message" => "Email and password required."], 400);
}

$email = filter_var($data->email, FILTER_VALIDATE_EMAIL);
$password = $data->password;

if (!$email) {
    send_json_response(["success" => false, "message" => "Invalid email."], 400);
}

$stmt = $conn->prepare("SELECT id, username, email, password, role FROM users WHERE email = ?");
if (!$stmt) {
    send_json_response(["success" => false, "message" => "Database error: " . $conn->error], 500);
}

$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
    send_json_response(["success" => false, "message" => "User not found."], 401);
}
$stmt->bind_result($id, $username, $email_db, $password_hash, $role);
$stmt->fetch();

if (!password_verify($password, $password_hash)) {
    send_json_response(["success" => false, "message" => "Incorrect password."], 401);
}

// Login success - set session
$_SESSION['user_id'] = $id;
$_SESSION['username'] = $username;
$_SESSION['email'] = $email_db;
$_SESSION['role'] = $role;

send_json_response([
    "success" => true,
    "message" => "Login successful.",
    "user" => [
        "id" => $id,
        "username" => $username,
        "email" => $email_db,
        "role" => $role
    ]
]);
$stmt->close();
$conn->close();
