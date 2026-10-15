<?php
require_once 'db.php';

function getAllOrders() {
    $conn = getConnection();
    $sql = "SELECT * FROM orders";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function insertOrder($userId, $totalAmount, $status) {
    $conn = getConnection();
    $sql = "INSERT INTO orders (user_id, order_date, total_amount, status) VALUES (?, NOW(), ?, ?)";
    $stmt = $conn->prepare($sql);
    $success = $stmt->execute([$userId, $totalAmount, $status]);

    if ($success) {
        return $conn->lastInsertId();
    }
    return -1;
}

function getOrdersByUserId($userId) {
    $conn = getConnection();
    $sql = "SELECT * FROM orders WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getOrderById($orderId) {
    $conn = getConnection();
    $sql = "SELECT * FROM orders WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    return $order ? $order : null;
}

function getAllOrdersWithUsers() {
    $conn = getConnection();
    $sql = "SELECT orders.*, users.name AS buyer_name, users.email AS buyer_email
            FROM orders
            JOIN users ON orders.user_id = users.id
            ORDER BY orders.order_date DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function updateOrderStatus($orderId, $newStatus) {
    $conn = getConnection();
    $sql = "UPDATE orders SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    return $stmt->execute([$newStatus, $orderId]);
}

function getSalesSummary() {
    $conn = getConnection();
    $sql = "SELECT COUNT(*) AS order_count, COALESCE(SUM(total_amount), 0) AS total_revenue
            FROM orders WHERE status != 'Cancelled'";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getOrderCountsByStatus() {
    $conn = getConnection();
    $sql = "SELECT status, COUNT(*) AS count FROM orders GROUP BY status";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getTopSellingProducts($limit = 5) {
    $conn = getConnection();
    $sql = "SELECT products.name, SUM(order_items.quantity) AS total_sold,
                   SUM(order_items.quantity * order_items.price_at_purchase) AS total_revenue
            FROM order_items
            JOIN orders ON order_items.order_id = orders.id
            JOIN products ON order_items.product_id = products.id
            WHERE orders.status != 'Cancelled'
            GROUP BY products.id, products.name
            ORDER BY total_sold DESC
            LIMIT $limit";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>