<?php
require_once 'db.php';

function ensureOrderItemSizeColumn() {
    static $ready = false;
    if ($ready) return;
    $conn = getConnection();
    $column = $conn->query("SHOW COLUMNS FROM order_items LIKE 'size'")->fetch(PDO::FETCH_ASSOC);
    if (!$column) $conn->exec("ALTER TABLE order_items ADD size VARCHAR(5) NOT NULL DEFAULT 'M'");
    $ready = true;
}

function getAllOrderItems() {
    ensureOrderItemSizeColumn();
    $conn = getConnection();
    $sql = "SELECT * FROM order_items";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function insertOrderItem($orderId, $productId, $quantity, $priceAtPurchase, $size = 'M') {
    ensureOrderItemSizeColumn();
    $conn = getConnection();
    $sql = "INSERT INTO order_items (order_id, product_id, quantity, price_at_purchase, size) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    return $stmt->execute([$orderId, $productId, $quantity, $priceAtPurchase, $size]);
}

function getOrderItemsByOrderId($orderId) {
    ensureOrderItemSizeColumn();
    $conn = getConnection();
    $sql = "SELECT * FROM order_items WHERE order_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$orderId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getItemsWithProductInfo($orderId) {
    ensureOrderItemSizeColumn();
    $conn = getConnection();
    $sql = "SELECT order_items.*, products.name AS product_name, products.image_url
            FROM order_items
            JOIN products ON order_items.product_id = products.id
            WHERE order_items.order_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$orderId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
