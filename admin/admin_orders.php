<?php
// Page purpose: Admin order management: update statuses and delete cancelled orders individually or in bulk.
session_start();
require_once __DIR__ . '/../auth_activity.php';

// Access control: this page is available only to signed-in administrators.
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}
if (($_SESSION['user_role'] ?? '') !== 'admin') {
    echo "<p style='font-family: Arial; margin: 60px;'>Access denied. Admins only. <a href='../dashboard.php'>Back to Dashboard</a></p>";
    exit();
}

// Data access: load order and stock operations used by the admin page.
require_once __DIR__ . '/../OrderDAO.php';
require_once __DIR__ . '/../OrderItemDAO.php';
require_once __DIR__ . '/../SizeStockDAO.php';

$statusOptions = ['Pending', 'Processing', 'Delivered', 'Completed', 'Cancelled'];
$csrfToken = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrfToken;

// Request handling: verify the session token, then perform the selected admin action.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedToken = $_POST['csrf_token'] ?? '';
    if (!is_string($postedToken) || !hash_equals($csrfToken, $postedToken)) {
        $_SESSION['admin_orders_message'] = 'The request could not be verified. Please try again.';
        $_SESSION['admin_orders_message_type'] = 'error';
    } else {
        $action = $_POST['action'] ?? 'update_status';
        if ($action === 'delete_cancelled_all') {
            try {
                $deletedCount = deleteAllCancelledOrders();
                $_SESSION['admin_orders_message'] = $deletedCount > 0
                    ? "Deleted $deletedCount cancelled order(s)."
                    : 'There are no cancelled orders to delete.';
                $_SESSION['admin_orders_message_type'] = $deletedCount > 0 ? 'success' : 'error';
            } catch (Throwable $error) {
                $_SESSION['admin_orders_message'] = 'Cancelled orders could not be deleted. Please try again.';
                $_SESSION['admin_orders_message_type'] = 'error';
            }
        } elseif ($action === 'delete_cancelled_one') {
            $orderId = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
            try {
                $deleted = $orderId ? deleteCancelledOrderById($orderId) : false;
                $_SESSION['admin_orders_message'] = $deleted
                    ? "Cancelled order #$orderId deleted."
                    : 'That order was not found or is no longer cancelled.';
                $_SESSION['admin_orders_message_type'] = $deleted ? 'success' : 'error';
            } catch (Throwable $error) {
                $_SESSION['admin_orders_message'] = 'The cancelled order could not be deleted. Please try again.';
                $_SESSION['admin_orders_message_type'] = 'error';
            }
        } elseif ($action === 'save_all_statuses') {
            $postedStatuses = $_POST['statuses'] ?? null;
            $valid = is_array($postedStatuses);
            if ($valid) {
                foreach ($postedStatuses as $orderId => $newStatus) {
                    if (!ctype_digit((string)$orderId) || !in_array($newStatus, $statusOptions, true) || !getOrderById((int)$orderId)) {
                        $valid = false;
                        break;
                    }
                }
            }
            if (!$valid) {
                $_SESSION['admin_orders_message'] = 'One or more order statuses were invalid. No changes were saved.';
                $_SESSION['admin_orders_message_type'] = 'error';
            } else {
                $updatedCount = 0;
                foreach ($postedStatuses as $orderId => $newStatus) {
                    $orderId = (int)$orderId;
                    $currentOrder = getOrderById($orderId);
                    if (!$currentOrder || $currentOrder['status'] === $newStatus) continue;
                    if ($newStatus === 'Cancelled' && $currentOrder['status'] !== 'Cancelled') {
                        // Stock restoration: return each line to the size that was ordered when cancelling.
                        foreach (getOrderItemsByOrderId($orderId) as $item) {
                            restoreSizeStock($item['product_id'], strtoupper($item['size'] ?? 'M'), (int)$item['quantity']);
                        }
                    }
                    if (updateOrderStatus($orderId, $newStatus)) $updatedCount++;
                }
                $_SESSION['admin_orders_message'] = "Saved status changes for $updatedCount order(s).";
                $_SESSION['admin_orders_message_type'] = 'success';
            }
        } else {
            $orderId = filter_input(INPUT_POST, 'update_order_id', FILTER_VALIDATE_INT);
            $newStatus = $_POST['statuses'][$orderId] ?? '';
            if ($orderId && in_array($newStatus, $statusOptions, true)) {
                $currentOrder = getOrderById($orderId);
                if ($newStatus === 'Cancelled' && $currentOrder && $currentOrder['status'] !== 'Cancelled') {
                    // Stock restoration: return each line to the size that was ordered when cancelling.
                    foreach (getOrderItemsByOrderId($orderId) as $item) {
                        restoreSizeStock($item['product_id'], strtoupper($item['size'] ?? 'M'), (int)$item['quantity']);
                    }
                }
                if ($currentOrder && updateOrderStatus($orderId, $newStatus)) {
                    $_SESSION['admin_orders_message'] = "Order #$orderId updated.";
                    $_SESSION['admin_orders_message_type'] = 'success';
                } else {
                    $_SESSION['admin_orders_message'] = 'The order status could not be updated.';
                    $_SESSION['admin_orders_message_type'] = 'error';
                }
            } else {
                $_SESSION['admin_orders_message'] = 'Choose a valid order status and try again.';
                $_SESSION['admin_orders_message_type'] = 'error';
            }
        }
    }
    header('Location: admin_orders.php');
    exit();
}

// Page data: load orders and count the cancelled ones for the bulk-delete control.
$orders = getAllOrdersWithUsers();
$cancelledOrderCount = count(array_filter($orders, function ($order) {
    return $order['status'] === 'Cancelled';
}));
$flashMessage = $_SESSION['admin_orders_message'] ?? '';
$flashType = $_SESSION['admin_orders_message_type'] ?? 'success';
unset($_SESSION['admin_orders_message'], $_SESSION['admin_orders_message_type']);
?>
<!-- Page display: render order details, status controls, and safe cancelled-order deletion actions. -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Orders - Clothify</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .status-form { display: flex; gap: 8px; align-items: center; }
        .status-form select { width: auto; padding: 6px 8px; }
        .status-form button { padding: 6px 12px; font-size: 13px; }
        .order-admin-actions { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; }
        .order-admin-actions form { margin: 0; }
        .order-admin-actions .btn-danger { padding: 6px 10px; font-size: 12px; white-space: nowrap; }
        .bulk-delete-bar { display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; margin: 0 0 14px; }
        .bulk-delete-bar form { margin: 0; }
        .bulk-delete-bar button { padding: 9px 14px; }
    </style>
</head>
<body class="app-body">
    <?php $isAdminPage = true; require __DIR__ . '/../includes/navbar.php'; ?>

    <main class="page">
        <h1 style="margin-bottom: 24px;">Manage Orders</h1>

        <?php if ($flashMessage !== ''): ?>
            <p class="message <?php echo $flashType === 'error' ? 'error' : 'success'; ?>"><?php echo htmlspecialchars($flashMessage); ?></p>
        <?php endif; ?>

        <?php if (count($orders) > 0): ?>
            <div class="bulk-delete-bar">
            <form id="orders-status-form" method="POST" action="admin_orders.php">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
            </form>
            <button type="submit" form="orders-status-form" name="action" value="save_all_statuses" class="btn">Save All</button>
            <?php if ($cancelledOrderCount > 0): ?>
            <form method="POST" action="admin_orders.php" onsubmit="return confirm('Permanently delete all <?php echo $cancelledOrderCount; ?> cancelled orders and their items?');">
                <input type="hidden" name="action" value="delete_cancelled_all">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                <button type="submit" class="btn btn-danger">Delete all cancelled orders (<?php echo $cancelledOrderCount; ?>)</button>
            </form>
            <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (count($orders) === 0): ?>
            <p class="empty-state">No orders have been placed yet.</p>
        <?php else: ?>
            <div class="card" style="padding: 0;">
                <table>
                    <tr>
                        <th style="padding-left: 20px;">Order #</th>
                        <th>Buyer</th>
                        <th>Items &amp; Sizes</th>
                        <th>Date</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th style="padding-right: 20px;">Actions</th>
                    </tr>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td style="padding-left: 20px;">#<?php echo (int)$order['id']; ?></td>
                            <td><?php echo htmlspecialchars($order['buyer_name']); ?></td>
                            <td><div class="order-items-list"><?php foreach (getItemsWithProductInfo($order['id']) as $item): ?><div><?php echo htmlspecialchars($item['product_name']); ?> &middot; <?php echo htmlspecialchars($item['size'] ?? 'M'); ?> &times; <?php echo (int)$item['quantity']; ?></div><?php endforeach; ?></div></td>
                            <td><?php echo htmlspecialchars($order['order_date']); ?></td>
                            <td>PHP <?php echo number_format((float)$order['total_amount'], 2); ?></td>
                            <td><span class="status-badge"><?php echo htmlspecialchars($order['status']); ?></span></td>
                            <td style="padding-right: 20px;">
                                <div class="order-admin-actions">
                                    <div class="status-form">
                                        <select form="orders-status-form" name="statuses[<?php echo (int)$order['id']; ?>]">
                                            <?php foreach ($statusOptions as $option): ?>
                                                <option value="<?php echo htmlspecialchars($option); ?>" <?php if ($order['status'] === $option) echo 'selected'; ?>><?php echo htmlspecialchars($option); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" form="orders-status-form" name="update_order_id" value="<?php echo (int)$order['id']; ?>" class="btn">Save</button>
                                    </div>
                                    <?php if ($order['status'] === 'Cancelled'): ?>
                                        <form method="POST" action="admin_orders.php" onsubmit="return confirm('Permanently delete cancelled order #<?php echo (int)$order['id']; ?> and its items?');">
                                            <input type="hidden" name="action" value="delete_cancelled_one">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="order_id" value="<?php echo (int)$order['id']; ?>">
                                            <button type="submit" class="btn btn-danger">Delete</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
