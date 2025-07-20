<?php
require_once '../../helpers/cors.php';
handle_cors();
require_once '../../config/config.php';
require_once '../../config/db.php';
require_once '../../helpers/response.php';

session_start(); // start session here

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->username, $data->email, $data->password)) {
    send_json_response(["success" => false, "message" => "Missing fields: username, email, and password are required."], 400);
    exit;
}

$username = htmlspecialchars(trim($data->username));
$email = filter_var($data->email, FILTER_VALIDATE_EMAIL);
$password = $data->password;

if (!$email) {
    send_json_response(["success" => false, "message" => "Invalid email format."], 400);
    exit;
}

if (strlen($password) < 6) {
    send_json_response(["success" => false, "message" => "Password must be at least 6 characters."], 400);
    exit;
}

try {
    $db = new PDO(DB_DSN, DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if email already registered
    $stmt = $db->prepare("SELECT id FROM users WHERE email = :email");
    $stmt->bindParam(':email', $email);
    $stmt->execute();

    if ($stmt->fetch()) {
        send_json_response(["success" => false, "message" => "Email already registered."], 409);
        exit;
    }

    // Hash password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Insert new user
    $stmt = $db->prepare("INSERT INTO users (username, email, password, created_at) VALUES (:username, :email, :password, NOW())");
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':password', $password_hash);

    if ($stmt->execute()) {
        // Get the inserted user ID
        $newUserId = $db->lastInsertId();

        // Set session variables
        $_SESSION['user_id'] = $newUserId;
        $_SESSION['username'] = $username;
        $_SESSION['email'] = $email;
        $_SESSION['role'] = 'user'; // or whatever default role you want

        send_json_response([
            "success" => true,
            "message" => "User registered and logged in successfully.",
            "user" => [
                "id" => $newUserId,
                "username" => $username,
                "email" => $email,
                "role" => 'user'
            ]
        ]);
    } else {
        send_json_response(["success" => false, "message" => "Failed to register user."], 500);
    }
} catch (PDOException $e) {
    send_json_response([
        "success" => false,
        "message" => "Database error: " . $e->getMessage()
    ], 500);
}

