<?php
require_once '../../helpers/cors.php';
handle_cors();
session_start();
session_unset();
session_destroy();
http_response_code(200);
echo json_encode(["success" => true, "message" => "Logged out successfully."]);
exit;
