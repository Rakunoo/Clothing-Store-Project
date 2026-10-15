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

require_once '../ProductDAO.php';

$productId = $_GET["id"] ?? $_POST["id"] ?? null;
$product = $productId ? getProductById($productId) : null;

if (!$product) {
    echo "<p style='font-family: Arial; margin: 60px;'>Product not found. <a href='admin_panel.php'>Back to Admin Panel</a></p>";
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    deleteProduct($productId);
    header("Location: admin_panel.php"); // Mawawala na sa admin_panel.php kapag dinelete
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Delete Product - Clothify</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body class="app-body">
    <?php $isAdminPage = true; require __DIR__ . '/../includes/navbar.php'; ?>
    <main class="page narrow admin-centered">
        <h1>Delete Product</h1>
        <div class="card">
            <p style="color: var(--muted);">Are you sure you want to delete:</p>
            <h3><?php echo htmlspecialchars($product["name"]); ?></h3>
            <p class="price">PHP <?php echo number_format($product["price"], 2); ?></p>

            <form method="POST" action="delete_product.php" style="margin-top: 20px;">
                <input type="hidden" name="id" value="<?php echo $product["id"]; ?>">
                <button type="submit" class="btn btn-danger">Yes, Delete</button>
                <a class="back" style="margin-left: 14px;" href="admin_panel.php">Cancel</a>
            </form>
        </div>
    </main>
</body>
</html>
