<?php
require_once '../../helpers/cors.php';
handle_cors();
require_once '../../config/config.php';
require_once '../../config/db.php';
require_once '../../helpers/response.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['user_id'])) {
    send_json_response([
        "success" => false,
        "message" => "Missing user_id."
    ], 400);
    exit;
}

$user_id = intval($input['user_id']);

try {
    $db = new PDO(DB_DSN, DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Total products
    $stmt = $db->prepare("SELECT COUNT(*) AS total_products FROM products WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $products = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total_products'];

    // Total stock quantity
    $stmt = $db->prepare("SELECT COALESCE(SUM(quantity), 0) AS total_stock FROM stock WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $stock = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total_stock'];

    // Total sales quantity & total sales revenue
    $stmt = $db->prepare("SELECT COALESCE(SUM(quantity), 0) AS total_sales, COALESCE(SUM(price * quantity), 0) AS total_revenue FROM sales WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $salesData = $stmt->fetch(PDO::FETCH_ASSOC);
    $sales = (int)$salesData['total_sales'];
    $revenue = (float)$salesData['total_revenue'];

    // Sales per day for last 7 days
    $stmt = $db->prepare("
        SELECT DATE(sold_at) AS sale_date, COALESCE(SUM(quantity), 0) AS total_quantity
        FROM sales
        WHERE user_id = :user_id
        AND sold_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
        GROUP BY sale_date
        ORDER BY sale_date ASC
    ");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $sales_per_day_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fill missing days
    $sales_per_day = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $sales_per_day[$date] = 0;
    }
    foreach ($sales_per_day_raw as $row) {
        $sales_per_day[$row['sale_date']] = (int)$row['total_quantity'];
    }

    $daily_sales = [];
    foreach ($sales_per_day as $date => $quantity) {
        $daily_sales[] = [
            "day" => $date,
            "sales" => $quantity
        ];
    }

    send_json_response([
        "success" => true,
        "data" => [
            "total_products" => $products,
            "total_stock_entries" => $stock,
            "total_sales" => $sales,
            "total_sales_amount" => $revenue,
            "daily_sales" => $daily_sales
        ]
    ]);
} catch (PDOException $e) {
    send_json_response([
        "success" => false,
        "message" => "Database error: " . $e->getMessage()
    ], 500);
}
