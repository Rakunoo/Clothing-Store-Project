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

$message = "";
$productId = $_GET["id"] ?? $_POST["id"] ?? null;
$product = $productId ? getProductById($productId) : null;

if (!$product) {
    echo "<p style='font-family: Arial; margin: 60px;'>Product not found. <a href='admin_panel.php'>Back to Admin Panel</a></p>";
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = $_POST["name"];
    $description = $_POST["description"];
    $price = (float) $_POST["price"];
    $stock = (int) $_POST["stock_quantity"];
    $category = $_POST["category"];
    $imageUrl = trim($_POST["image_url"]);

    $success = updateProduct($productId, $name, $description, $price, $stock, $category, $imageUrl);

    if ($success) {
        header("Location: admin_panel.php");
        exit();
    } else {
        $message = "Update failed.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Product - Clothify</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body class="app-body">
    <?php $isAdminPage = true; require __DIR__ . '/../includes/navbar.php'; ?>
    <main class="page narrow admin-centered">
        <p><a class="back" href="admin_panel.php">&larr; Back to Admin Panel</a></p>
        <h1>Edit Product</h1>

        <div class="card">
            <?php if ($message): ?>
                <p class="message error"><?php echo $message; ?></p>
            <?php endif; ?>

            <form method="POST" action="edit_product.php">
                <input type="hidden" name="id" value="<?php echo $product["id"]; ?>">
                <label>Product Name</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($product["name"]); ?>" required>
                <label>Description</label>
                <textarea name="description" required><?php echo htmlspecialchars($product["description"]); ?></textarea>
                <label>Price</label>
                <input type="number" step="0.01" name="price" value="<?php echo $product["price"]; ?>" required>
                <label>Stock Quantity</label>
                <input type="number" name="stock_quantity" value="<?php echo $product["stock_quantity"]; ?>" required>
                <label>Category</label>
                <select name="category" required>
                    <option value="MEN" <?php if ($product["category"] === "MEN") echo "selected"; ?>>MEN</option>
                    <option value="WOMEN" <?php if ($product["category"] === "WOMEN") echo "selected"; ?>>WOMEN</option>
                </select>
                <label>Image URL</label>
                <input type="text" name="image_url" value="<?php echo htmlspecialchars($product["image_url"] ?? ""); ?>" placeholder="https://example.com/image.jpg">
                <button type="submit" class="btn" style="margin-top: 18px;">Save Changes</button>
            </form>
        </div>
    </main>
</body>
</html>
