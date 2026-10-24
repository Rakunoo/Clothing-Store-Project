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

/**
 * Build an inclusive date-range filter for order queries.
 * Dates are validated first, and the end date is expanded to the next day so
 * the whole selected day is included. The column name is supplied internally.
 */
function orderDateRangeFilter($startDate, $endDate, array &$params, $column = 'order_date') {
    $conditions = [];

    $start = is_string($startDate) && $startDate !== ''
        ? DateTimeImmutable::createFromFormat('!Y-m-d', $startDate)
        : false;
    if ($start && $start->format('Y-m-d') === $startDate) {
        $conditions[] = $column . ' >= ?';
        $params[] = $start->format('Y-m-d 00:00:00');
    }

    $end = is_string($endDate) && $endDate !== ''
        ? DateTimeImmutable::createFromFormat('!Y-m-d', $endDate)
        : false;
    if ($end && $end->format('Y-m-d') === $endDate) {
        $conditions[] = $column . ' < ?';
        $params[] = $end->modify('+1 day')->format('Y-m-d 00:00:00');
    }

    return $conditions ? ' AND ' . implode(' AND ', $conditions) : '';
}

// Review note: Calculate order count and revenue excluding cancelled orders, optionally within a date range.
function getSalesSummary($startDate = null, $endDate = null) {
    $conn = getConnection();
    $params = [];
    $dateFilter = orderDateRangeFilter($startDate, $endDate, $params);
    $sql = "SELECT COUNT(*) AS order_count, COALESCE(SUM(total_amount), 0) AS total_revenue
            FROM orders WHERE status != 'Cancelled'" . $dateFilter;
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Return a complete revenue series for a date range.
 * Empty days, weeks, or months are filled with zero so the chart keeps a
 * continuous timeline. Only non-cancelled orders are included.
 */
function getSalesSeries($period = 'daily', $startDate = null, $endDate = null) {
    $period = strtolower((string)$period);
    if (!in_array($period, ['daily', 'weekly', 'monthly'], true)) {
        $period = 'daily';
    }

    $rangeStart = is_string($startDate) && $startDate !== ''
        ? DateTimeImmutable::createFromFormat('!Y-m-d', $startDate)
        : false;
    $rangeEnd = is_string($endDate) && $endDate !== ''
        ? DateTimeImmutable::createFromFormat('!Y-m-d', $endDate)
        : false;
    if (!$rangeStart || $rangeStart->format('Y-m-d') !== $startDate) {
        $rangeStart = new DateTimeImmutable('today -29 days');
    }
    if (!$rangeEnd || $rangeEnd->format('Y-m-d') !== $endDate) {
        $rangeEnd = new DateTimeImmutable('today');
    }
    if ($rangeStart > $rangeEnd) {
        [$rangeStart, $rangeEnd] = [$rangeEnd, $rangeStart];
    }

    // Bucket boundaries are aligned to the start of each day, week, or month.
    $bucketStart = $rangeStart;
    $bucketEnd = $rangeEnd;
    if ($period === 'weekly') {
        $bucketStart = $rangeStart->modify('-' . ((int)$rangeStart->format('N') - 1) . ' days');
        $bucketEnd = $rangeEnd->modify('-' . ((int)$rangeEnd->format('N') - 1) . ' days');
        $groupExpression = 'DATE_SUB(DATE(order_date), INTERVAL WEEKDAY(order_date) DAY)';
    } elseif ($period === 'monthly') {
        $bucketStart = $rangeStart->modify('first day of this month');
        $bucketEnd = $rangeEnd->modify('first day of this month');
        $groupExpression = "DATE_FORMAT(order_date, '%Y-%m-01')";
    } else {
        $groupExpression = 'DATE(order_date)';
    }

    $conn = getConnection();
    $params = [];
    $dateFilter = orderDateRangeFilter(
        $rangeStart->format('Y-m-d'),
        $rangeEnd->format('Y-m-d'),
        $params
    );
    $sql = "SELECT $groupExpression AS bucket,
                   COALESCE(SUM(total_amount), 0) AS revenue,
                   COUNT(*) AS order_count
            FROM orders
            WHERE status != 'Cancelled'" . $dateFilter . "
            GROUP BY bucket
            ORDER BY bucket";
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);

    $valuesByBucket = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $valuesByBucket[(string)$row['bucket']] = [
            'revenue' => (float)$row['revenue'],
            'order_count' => (int)$row['order_count'],
        ];
    }

    $series = [];
    if ($period === 'daily') {
        for ($day = $bucketStart; $day <= $bucketEnd; $day = $day->modify('+1 day')) {
            $key = $day->format('Y-m-d');
            $series[] = [
                'key' => $key,
                'label' => $day->format('M j'),
                'full_label' => $day->format('M j, Y'),
                'revenue' => $valuesByBucket[$key]['revenue'] ?? 0.0,
                'order_count' => $valuesByBucket[$key]['order_count'] ?? 0,
            ];
        }
    } elseif ($period === 'weekly') {
        for ($week = $bucketStart; $week <= $bucketEnd; $week = $week->modify('+7 days')) {
            $key = $week->format('Y-m-d');
            $weekEnd = $week->modify('+6 days');
            $series[] = [
                'key' => $key,
                'label' => $week->format('M j'),
                'full_label' => 'Week of ' . $week->format('M j') . ' - ' . $weekEnd->format('M j, Y'),
                'revenue' => $valuesByBucket[$key]['revenue'] ?? 0.0,
                'order_count' => $valuesByBucket[$key]['order_count'] ?? 0,
            ];
        }
    } else {
        for ($month = $bucketStart; $month <= $bucketEnd; $month = $month->modify('+1 month')) {
            $key = $month->format('Y-m-01');
            $series[] = [
                'key' => $key,
                'label' => $month->format('M Y'),
                'full_label' => $month->format('F Y'),
                'revenue' => $valuesByBucket[$key]['revenue'] ?? 0.0,
                'order_count' => $valuesByBucket[$key]['order_count'] ?? 0,
            ];
        }
    }

    return $series;
}

// Review note: Group order totals by their current status, optionally within a date range.
function getOrderCountsByStatus($startDate = null, $endDate = null) {
    $conn = getConnection();
    $params = [];
    $dateFilter = orderDateRangeFilter($startDate, $endDate, $params);
    $sql = "SELECT status, COUNT(*) AS count
            FROM orders
            WHERE 1 = 1" . $dateFilter . "
            GROUP BY status
            ORDER BY status";
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Review note: Rank products by quantities sold and related revenue, optionally within a date range.
function getTopSellingProducts($limit = 5, $startDate = null, $endDate = null) {
    $conn = getConnection();
    $limit = max(1, (int)$limit);
    $params = [];
    $dateFilter = orderDateRangeFilter($startDate, $endDate, $params, 'orders.order_date');
    $sql = "SELECT products.name, SUM(order_items.quantity) AS total_sold,
                   SUM(order_items.quantity * order_items.price_at_purchase) AS total_revenue
            FROM order_items
            JOIN orders ON order_items.order_id = orders.id
            JOIN products ON order_items.product_id = products.id
            WHERE orders.status != 'Cancelled'" . $dateFilter . "
            GROUP BY products.id, products.name
            ORDER BY total_sold DESC, total_revenue DESC
            LIMIT $limit";
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
