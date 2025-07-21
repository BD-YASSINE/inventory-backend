<?php
session_start();
require_once '../../helpers/cors.php';
handle_cors();
require_once '../../config/config.php';
require_once '../../config/db.php';
require_once '../../helpers/response.php';

// ✅ تحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    send_json_response([
        "success" => false,
        "message" => "Session expired. Please log in again."
    ], 401);
    exit;
}

$user_id = intval($_SESSION['user_id']);
$role = $_SESSION['role']; // 👈 خذ القيمة الصحيحة فقط

try {
    $db = new PDO(DB_DSN, DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $isAdmin = ($role === 'admin');

    // ✅ إجمالي المنتجات
    if ($isAdmin) {
        $stmt = $db->query("SELECT COUNT(*) AS total_products FROM products");
    } else {
        $stmt = $db->prepare("SELECT COUNT(*) AS total_products FROM products WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
    }
    $products = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total_products'];

    // ✅ إجمالي المخزون
    if ($isAdmin) {
        $stmt = $db->query("SELECT COALESCE(SUM(quantity), 0) AS total_stock FROM stock");
    } else {
        $stmt = $db->prepare("SELECT COALESCE(SUM(quantity), 0) AS total_stock FROM stock WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
    }
    $stock = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total_stock'];

    // ✅ إجمالي المبيعات والعائد
    if ($isAdmin) {
        $stmt = $db->query("SELECT COALESCE(SUM(quantity), 0) AS total_sales, COALESCE(SUM(price * quantity), 0) AS total_revenue FROM sales");
    } else {
        $stmt = $db->prepare("SELECT COALESCE(SUM(quantity), 0) AS total_sales, COALESCE(SUM(price * quantity), 0) AS total_revenue FROM sales WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
    }
    $salesData = $stmt->fetch(PDO::FETCH_ASSOC);
    $sales = (int)$salesData['total_sales'];
    $revenue = (float)$salesData['total_revenue'];

    // ✅ المبيعات اليومية لآخر 7 أيام
    if ($isAdmin) {
        $stmt = $db->query("
            SELECT DATE(sold_at) AS sale_date, COALESCE(SUM(quantity), 0) AS total_quantity
            FROM sales
            WHERE sold_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
            GROUP BY sale_date
            ORDER BY sale_date ASC
        ");
    } else {
        $stmt = $db->prepare("
            SELECT DATE(sold_at) AS sale_date, COALESCE(SUM(quantity), 0) AS total_quantity
            FROM sales
            WHERE user_id = :user_id AND sold_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
            GROUP BY sale_date
            ORDER BY sale_date ASC
        ");
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
    }
    $sales_per_day_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ✅ ملء الأيام الفارغة
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

    // ✅ إرسال البيانات
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

