<?php
// Page purpose: Admin reports: gathers and displays sales totals, status counts, and top products.
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

require_once '../OrderDAO.php';

$statusCounts = getOrderCountsByStatus();
$topProducts = getTopSellingProducts(5);
?>
<!-- Page display: the HTML below renders the prepared data and forms. -->
<!DOCTYPE html>
<html>
<head>
    <title>Sales Report - Clothify</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .status-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid var(--border); font-size: 15px; }
        .status-row:last-child { border-bottom: none; }
    </style>
</head>
<body class="app-body">
    <?php $isAdminPage = true; require __DIR__ . '/../includes/navbar.php'; ?>

    <div class="page">
        <h1 style="margin-bottom: 24px;">Sales Report</h1>

        <div class="card">
            <h2>Orders by Status</h2>
            <?php if (count($statusCounts) === 0): ?>
                <p class="empty-state" style="padding: 10px 0;">No orders yet.</p>
            <?php else: ?>
                <?php foreach ($statusCounts as $row): ?>
                    <div class="status-row">
                        <span><?php echo htmlspecialchars(ucfirst(strtolower($row["status"]))); ?></span>
                        <span><?php echo $row["count"]; ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2>Top Selling Products</h2>
            <?php if (count($topProducts) === 0): ?>
                <p class="empty-state" style="padding: 10px 0;">No sales yet.</p>
            <?php else: ?>
                <table>
                    <tr>
                        <th>Product</th>
                        <th>Units Sold</th>
                        <th>Revenue</th>
                    </tr>
                    <?php foreach ($topProducts as $product): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($product["name"]); ?></td>
                            <td><?php echo $product["total_sold"]; ?></td>
                            <td>PHP <?php echo number_format($product["total_revenue"], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
