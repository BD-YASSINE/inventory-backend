<?php
require_once '../../helpers/cors.php';
handle_cors();
require_once '../../config/config.php';
require_once '../../config/db.php';
require_once '../../helpers/response.php';

send_json_response(["success" => true, "message" => "Endpoint works, implement your logic."]);
