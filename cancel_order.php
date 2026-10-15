<?php
session_start();
require_once __DIR__ . '/auth_activity.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

require_once 'OrderDAO.php';
require_once 'OrderItemDAO.php';
require_once 'ProductDAO.php';
require_once __DIR__ . '/SizeStockDAO.php';

$orderId = $_GET["id"] ?? $_POST["id"] ?? null;
$order = $orderId ? getOrderById($orderId) : null;

if (!$order || (int) $order["user_id"] !== (int) $_SESSION["user_id"]) {
    echo "<p style='font-family: Arial; margin: 60px;'>Order not found. <a href='my_orders.php'>Back to My Orders</a></p>";
    exit();
}

if ($order["status"] !== "Pending") {
    echo "<p style='font-family: Arial; margin: 60px;'>This order can no longer be cancelled (status: " . htmlspecialchars($order["status"]) . "). <a href='my_orders.php'>Back to My Orders</a></p>";
    exit();
}

$items = getItemsWithProductInfo($orderId);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    foreach ($items as $item) {
        restoreSizeStock($item["product_id"], strtoupper($item["size"] ?? 'M'), (int)$item["quantity"]);
    }

    updateOrderStatus($orderId, "Cancelled");
    header("Location: my_orders.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Cancel Order - Clothify</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="app-body">
    <?php $isAdminPage = false; require __DIR__ . '/includes/navbar.php'; ?>
    <div class="page narrow">
        <h1>Cancel Order</h1>
        <div class="card">
            <p style="color: var(--muted);">Are you sure you want to cancel Order #<?php echo $order["id"]; ?>?</p>

            <div class="order-items-list" style="margin: 14px 0;">
                <?php foreach ($items as $item): ?>
                    <div>
                        <?php if (!empty($item["image_url"])): ?>
                            <img class="mini-thumb" src="<?php echo htmlspecialchars($item["image_url"]); ?>" alt="">
                        <?php endif; ?>
                        <?php echo htmlspecialchars($item["product_name"]); ?> &middot; Size <?php echo htmlspecialchars($item["size"] ?? 'M'); ?> &times; <?php echo $item["quantity"]; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <p class="price">Total: PHP <?php echo number_format($order["total_amount"], 2); ?></p>

            <form method="POST" action="cancel_order.php" style="margin-top: 20px;">
                <input type="hidden" name="id" value="<?php echo $order["id"]; ?>">
                <button type="submit" class="btn btn-danger">Yes, Cancel Order</button>
                <a class="back" style="margin-left: 14px;" href="my_orders.php">Never mind</a>
            </form>
        </div>
    </div>
</body>
</html>
