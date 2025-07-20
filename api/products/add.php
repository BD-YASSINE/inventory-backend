<?php
require_once '../../helpers/cors.php';
handle_cors();
require_once '../../config/config.php';
require_once '../../config/db.php';
require_once '../../helpers/response.php';

// ✅ Simulate getting user ID (you can change this to token-based later)
session_start();
if (!isset($_SESSION['user_id'])) {
    send_json_response(['success' => false, 'message' => 'User not authenticated.'], 401);
}
$user_id = $_SESSION['user_id'];

// ✅ Read input data
$data = json_decode(file_get_contents("php://input"), true);

$name = $data['name'] ?? '';
$description = $data['description'] ?? '';
$category = $data['category'] ?? '';

if (!$name || !$category) {
    send_json_response(['success' => false, 'message' => 'Name and category are required.'], 400);
}

// ✅ Insert into products table
$stmt = $conn->prepare("INSERT INTO products (name, description, category, user_id) VALUES (?, ?, ?, ?)");
$stmt->bind_param("sssi", $name, $description, $category, $user_id);

if ($stmt->execute()) {
    send_json_response(['success' => true, 'message' => 'Product added successfully.']);
} else {
    send_json_response(['success' => false, 'message' => 'Failed to add product.']);
}
?>
