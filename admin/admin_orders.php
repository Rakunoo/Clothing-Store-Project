<?php
session_start();
require_once __DIR__ . '/../auth_activity.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit();
}

if ($_SESSION["user_role"] !== "admin") {
    echo "<p style='font-family: Arial; margin: 60px;'>Access denied. Admins only. <a href='../dashboard.php'>Back to Dashboard</a></p>";
    exit();
}

require_once '../OrderDAO.php'; // Line 14 to 16 nirerequire niya para mag-interact sa DB
require_once '../OrderItemDAO.php';
require_once '../ProductDAO.php';
require_once '../SizeStockDAO.php';

$statusOptions = ["Pending", "Processing", "Delivered", "Completed", "Cancelled"];

if ($_SERVER["REQUEST_METHOD"] === "POST") { // Validates kung minamatch niya 'yung nasa array natin ng $statusOptions
    $orderId = $_POST["order_id"];
    $newStatus = $_POST["status"];

    if (in_array($newStatus, $statusOptions, true)) {
        $currentOrder = getOrderById($orderId);

        if ($newStatus === "Cancelled" && $currentOrder && $currentOrder["status"] !== "Cancelled") {
            // Admin cancellation: restore inventory to the size saved on each order item.
            $items = getOrderItemsByOrderId($orderId);
            foreach ($items as $item) {
                restoreSizeStock($item["product_id"], strtoupper($item["size"] ?? 'M'), (int)$item["quantity"]);
            }
        }

        updateOrderStatus($orderId, $newStatus);
    }
    header("Location: admin_orders.php");
    exit();
}

$orders = getAllOrdersWithUsers(); //Finefetch order details sa database para malaman kung sino at anong inorder
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Orders - Clothify</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .status-form { display: flex; gap: 8px; align-items: center; }
        .status-form select { width: auto; padding: 6px 8px; }
        .status-form button { padding: 6px 12px; font-size: 13px; }
    </style>
</head>
<body class="app-body">
    <?php $isAdminPage = true; require __DIR__ . '/../includes/navbar.php'; ?>

    <div class="page">
        <h1 style="margin-bottom: 24px;">Manage Orders</h1>

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
                        <th style="padding-right: 20px;">Update</th>
                    </tr>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td style="padding-left: 20px;">#<?php echo $order["id"]; ?></td>
                            <td><?php echo htmlspecialchars($order["buyer_name"]); ?></td>
                            <td><div class="order-items-list"><?php foreach (getItemsWithProductInfo($order["id"]) as $item): ?><div><?php echo htmlspecialchars($item["product_name"]); ?> &middot; <?php echo htmlspecialchars($item["size"] ?? 'M'); ?> &times; <?php echo (int)$item["quantity"]; ?></div><?php endforeach; ?></div></td>
                            <td><?php echo $order["order_date"]; ?></td>
                            <td>PHP <?php echo number_format($order["total_amount"], 2); ?></td>
                            <td><span class="status-badge"><?php echo htmlspecialchars($order["status"]); ?></span></td>
                            <td style="padding-right: 20px;">
                                <form method="POST" action="admin_orders.php" class="status-form">
                                    <input type="hidden" name="order_id" value="<?php echo $order["id"]; ?>">
                                    <select name="status">
                                        <?php foreach ($statusOptions as $option): ?>
                                            <option value="<?php echo $option; ?>" <?php if ($order["status"] === $option) echo "selected"; ?>>
                                                <?php echo $option; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="btn">Save</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
