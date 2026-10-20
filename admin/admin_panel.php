<?php
// Page purpose: Admin catalog: lists products and provides links to product-management actions.
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

require_once '../ProductDAO.php';

$products = getAllProducts(); // Checks kung anong order meron
?>
<!-- Page display: the HTML below renders the prepared data and forms. -->
<!DOCTYPE html>
<html>
<head>
    <title>Admin Panel - Clothify</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body class="app-body">
    <?php $isAdminPage = true; require __DIR__ . '/../includes/navbar.php'; ?>

    <div class="page">
        <div class="top-bar">
            <h1>Admin Panel</h1>
            <a class="btn" href="add_product.php">+ Add New Product</a>
        </div>

        <div class="card" style="padding: 0;">
            <table>
                <tr>
                    <th style="padding-left: 20px;">ID</th>
                    <th>Name</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Category</th>
                    <th style="padding-right: 20px;">Actions</th>
                </tr>
                <?php foreach ($products as $product): ?>
                    <tr>
                        <td style="padding-left: 20px;"><?php echo $product["id"]; ?></td>
                        <td><?php echo htmlspecialchars($product["name"]); ?></td>
                        <td>PHP <?php echo number_format($product["price"], 2); ?></td>
                        <td><?php echo $product["stock_quantity"]; ?></td>
                        <td><?php echo htmlspecialchars($product["category"]); ?></td>
                        <td style="padding-right: 20px;">
                            <!-- Inventory link: size quantities are managed separately from product details. -->
                            <a class="small-link edit" href="manage_size_stock.php?id=<?php echo (int)$product["id"]; ?>">Sizes / Stock</a>
                            <a class="small-link edit" href="edit_product.php?id=<?php echo $product["id"]; ?>">Edit</a>
                            <a class="small-link delete" href="delete_product.php?id=<?php echo $product["id"]; ?>">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
</body>
</html>
