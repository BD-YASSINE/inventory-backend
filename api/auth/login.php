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
    exit;
}

$email = filter_var($data->email, FILTER_VALIDATE_EMAIL);
$password = $data->password;

if (!$email) {
    send_json_response(["success" => false, "message" => "Invalid email."], 400);
    exit;
}

try {
    $db = new PDO(DB_DSN, DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $db->prepare("SELECT id, username, email, password, role FROM users WHERE email = :email");
    $stmt->bindParam(':email', $email);
    $stmt->execute();

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        send_json_response(["success" => false, "message" => "Email does not exist, Create an account."], 401);
        exit;
    }

    if (!password_verify($password, $user['password'])) {
        send_json_response(["success" => false, "message" => "Incorrect password, please try again."], 401);
        exit;
    }

    // Login success - set session variables
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];

    send_json_response([
        "success" => true,
        "message" => "Login successful.",
        "user" => [
            "id" => $user['id'],
            "username" => $user['username'],
            "email" => $user['email'],
            "role" => $user['role']
        ]
    ]);
} catch (PDOException $e) {
    send_json_response([
        "success" => false,
        "message" => "Database error: " . $e->getMessage()
    ], 500);
}

