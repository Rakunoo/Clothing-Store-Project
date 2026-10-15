<?php
session_start();
require_once __DIR__ . '/auth_activity.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

require_once 'OrderDAO.php';
require_once 'OrderItemDAO.php';

$userId = $_SESSION["user_id"];
$orders = getOrdersByUserId($userId);
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Orders - Clothify</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="app-body">
    <?php $isAdminPage = false; require __DIR__ . '/includes/navbar.php'; ?>

    <div class="page">
        <h1 style="margin-bottom: 24px;">My Orders</h1> 

        <?php if (count($orders) === 0): ?> 
            <p class="empty-state">You haven't placed any orders yet. <a href="shop.php">Go shopping</a>.</p> 
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
                <?php $items = getItemsWithProductInfo($order["id"]); ?>
                <div class="card">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <strong>Order #<?php echo $order["id"]; ?></strong>
                        <span class="status-badge"><?php echo htmlspecialchars($order["status"]); ?></span>
                    </div>

                    <div class="order-items-list">
                        <?php foreach ($items as $item): ?>
                            <div>
                                <?php if (!empty($item["image_url"])): ?>
                                    <img class="mini-thumb" src="<?php echo htmlspecialchars($item["image_url"]); ?>" alt="">
                                <?php endif; ?>
                                <?php echo htmlspecialchars($item["product_name"]); ?> &middot; Size <?php echo htmlspecialchars($item["size"] ?? 'M'); ?> &times; <?php echo $item["quantity"]; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <p style="margin-top: 10px; font-size: 13px; color: var(--muted);">
                        <?php echo $order["order_date"]; ?> &middot; PHP <?php echo number_format($order["total_amount"], 2); ?>
                    </p>

                    <?php if ($order["status"] === "Pending"): ?>
                        <a class="small-link delete" href="cancel_order.php?id=<?php echo $order["id"]; ?>">Cancel Order</a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>
