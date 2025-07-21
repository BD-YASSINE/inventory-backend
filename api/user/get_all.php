<?php
require_once '../../helpers/cors.php';
handle_cors();
require_once '../../config/config.php';
require_once '../../config/db.php';
require_once '../../helpers/response.php';

session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    send_json_response(["success" => false, "message" => "Unauthorized"], 403);
    exit;
}

try {
    $db = new PDO(DB_DSN, DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "
        SELECT
            u.id,
            u.username,
            u.email,
            u.role,
            u.created_at,
            COALESCE(p.product_count, 0) AS total_products,
            COALESCE(st.stock_count, 0) AS total_stock_entries,
            COALESCE(sa.sales_count, 0) AS total_sales
        FROM users u
        LEFT JOIN (
            SELECT user_id, COUNT(*) AS product_count
            FROM products
            GROUP BY user_id
        ) p ON u.id = p.user_id
        LEFT JOIN (
            SELECT user_id, COALESCE(SUM(quantity), 0) AS stock_count
            FROM stock
            GROUP BY user_id
        ) st ON u.id = st.user_id
        LEFT JOIN (
            SELECT user_id, COALESCE(SUM(quantity), 0) AS sales_count
            FROM sales
            GROUP BY user_id
        ) sa ON u.id = sa.user_id
        WHERE u.role != 'admin'
        ORDER BY u.username ASC
    ";

    $stmt = $db->query($sql);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    send_json_response(["success" => true, "users" => $users]);

} catch (PDOException $e) {
    send_json_response(["success" => false, "message" => "Database error: " . $e->getMessage()], 500);
}



