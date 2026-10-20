<?php
// File purpose: Order data access: reads, creates, updates, and summarizes order records.
require_once 'db.php';

// Review note: Load all order records.
function getAllOrders() {
    $conn = getConnection();
    $sql = "SELECT * FROM orders";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Review note: Create an order header and return its new database ID.
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

// Review note: Load a customer's orders and number them within that account, rather than showing the system-wide ID.
function getOrdersByUserId($userId) {
    $conn = getConnection();
    $sql = "SELECT current_order.*,
                   (SELECT COUNT(*) FROM orders AS account_orders
                    WHERE account_orders.user_id = current_order.user_id
                      AND account_orders.id <= current_order.id) AS account_order_number
            FROM orders AS current_order
            WHERE current_order.user_id = ?
            ORDER BY current_order.order_date DESC, current_order.id DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Review note: Look up one order by its unique ID.
function getOrderById($orderId) {
    $conn = getConnection();
    $sql = "SELECT * FROM orders WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    return $order ? $order : null;
}

// Review note: Include every order; a missing account match should not hide an order from admins.
function getAllOrdersWithUsers() {
    $conn = getConnection();
    $sql = "SELECT orders.*, COALESCE(users.name, 'Unknown customer') AS buyer_name, users.email AS buyer_email
            FROM orders
            LEFT JOIN users ON orders.user_id = users.id
            ORDER BY orders.order_date DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Review note: Save a validated order status change.
function updateOrderStatus($orderId, $newStatus) {
    $conn = getConnection();
    $sql = "UPDATE orders SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    return $stmt->execute([$newStatus, $orderId]);
}

// Review note: Permanently remove an owner's cancelled order and its line items in one transaction.
function deleteCancelledOrder($orderId, $userId) {
    $conn = getConnection();
    $conn->beginTransaction();
    try {
        $check = $conn->prepare("SELECT id FROM orders WHERE id = ? AND user_id = ? AND status = 'Cancelled' FOR UPDATE");
        $check->execute([(int)$orderId, (int)$userId]);
        if (!$check->fetchColumn()) {
            $conn->rollBack();
            return false;
        }

        $deleteItems = $conn->prepare('DELETE FROM order_items WHERE order_id = ?');
        $deleteItems->execute([(int)$orderId]);
        $deleteOrder = $conn->prepare("DELETE FROM orders WHERE id = ? AND user_id = ? AND status = 'Cancelled'");
        $deleteOrder->execute([(int)$orderId, (int)$userId]);
        $deleted = $deleteOrder->rowCount() === 1;
        $conn->commit();
        return $deleted;
    } catch (Throwable $error) {
        if ($conn->inTransaction()) $conn->rollBack();
        throw $error;
    }
}

// Review note: Allow an administrator to delete one record only after verifying it is cancelled.
function deleteCancelledOrderById($orderId) {
    $conn = getConnection();
    $conn->beginTransaction();
    try {
        $check = $conn->prepare("SELECT id FROM orders WHERE id = ? AND status = 'Cancelled' FOR UPDATE");
        $check->execute([(int)$orderId]);
        if (!$check->fetchColumn()) {
            $conn->rollBack();
            return false;
        }

        $deleteItems = $conn->prepare('DELETE FROM order_items WHERE order_id = ?');
        $deleteItems->execute([(int)$orderId]);
        $deleteOrder = $conn->prepare("DELETE FROM orders WHERE id = ? AND status = 'Cancelled'");
        $deleteOrder->execute([(int)$orderId]);
        $deleted = $deleteOrder->rowCount() === 1;
        $conn->commit();
        return $deleted;
    } catch (Throwable $error) {
        if ($conn->inTransaction()) $conn->rollBack();
        throw $error;
    }
}

// Review note: Delete every cancelled order and its line items within one transaction.
function deleteAllCancelledOrders() {
    $conn = getConnection();
    $conn->beginTransaction();
    try {
        $ids = $conn->query("SELECT id FROM orders WHERE status = 'Cancelled' FOR UPDATE")->fetchAll(PDO::FETCH_COLUMN);
        $ids = array_map('intval', $ids);
        if (!$ids) {
            $conn->commit();
            return 0;
        }

        $deleteItems = $conn->prepare('DELETE FROM order_items WHERE order_id = ?');
        $deleteOrder = $conn->prepare("DELETE FROM orders WHERE id = ? AND status = 'Cancelled'");
        $deletedCount = 0;
        foreach ($ids as $id) {
            $deleteItems->execute([$id]);
            $deleteOrder->execute([$id]);
            $deletedCount += $deleteOrder->rowCount();
        }
        $conn->commit();
        return $deletedCount;
    } catch (Throwable $error) {
        if ($conn->inTransaction()) $conn->rollBack();
        throw $error;
    }
}

// Review note: Calculate order count and revenue excluding cancelled orders.
function getSalesSummary() {
    $conn = getConnection();
    $sql = "SELECT COUNT(*) AS order_count, COALESCE(SUM(total_amount), 0) AS total_revenue
            FROM orders WHERE status != 'Cancelled'";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Review note: Return one non-cancelled revenue total for each of the last 30 calendar days.
function getDailySalesLast30Days() {
    $conn = getConnection();
    $sql = "SELECT DATE(order_date) AS sale_day, COALESCE(SUM(total_amount), 0) AS revenue
            FROM orders
            WHERE status != 'Cancelled'
              AND order_date >= CURDATE() - INTERVAL 29 DAY
              AND order_date < CURDATE() + INTERVAL 1 DAY
            GROUP BY DATE(order_date)
            ORDER BY sale_day";
    $rows = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    $revenueByDay = [];
    foreach ($rows as $row) $revenueByDay[$row['sale_day']] = (float)$row['revenue'];

    $dailySales = [];
    for ($offset = 29; $offset >= 0; $offset--) {
        $day = date('Y-m-d', strtotime("-$offset days"));
        $dailySales[] = ['day' => $day, 'revenue' => $revenueByDay[$day] ?? 0.0];
    }
    return $dailySales;
}

// Review note: Group order totals by their current status.
function getOrderCountsByStatus() {
    $conn = getConnection();
    $sql = "SELECT status, COUNT(*) AS count FROM orders GROUP BY status";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Review note: Rank products by quantities sold and related revenue.
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
