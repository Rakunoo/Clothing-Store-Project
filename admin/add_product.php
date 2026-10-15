<?php
session_start();
require_once __DIR__ . '/../auth_activity.php';

if (!isset($_SESSION["user_id"])) { // Check niya muna kung naka log-in, if hindi, redirect sa login page
    header("Location: ../login.php");
    exit();
}

if ($_SESSION["user_role"] !== "admin") { // Pag di ka admin, di mo ma-access admin panel
    echo "<p style='font-family: Arial; margin: 60px;'>Access denied. Admins only. <a href='../dashboard.php'>Back to Dashboard</a></p>";
    exit();
}

require_once '../ProductDAO.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") { // Post gamit para nakatago sa URL, hindi maraming characters sa URL, mas malinis
    $name = $_POST["name"];
    $description = $_POST["description"];
    $price = (float) $_POST["price"];
    // Inventory note: per-size quantities are entered after creating this product.
    $stock = 0;
    $category = $_POST["category"];
    $imageUrl = trim($_POST["image_url"]);

    $success = insertProduct($name, $description, $price, $stock, $category, $imageUrl);

    if ($success) {
        header("Location: admin_panel.php"); // Pag tama, magu-update 'yung products sa admin_panel
        exit();
    } else {
        $message = "Failed to add product.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add Product - Clothify</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body class="app-body">
    <?php $isAdminPage = true; require __DIR__ . '/../includes/navbar.php'; ?>
    <main class="page narrow admin-centered">
        <p><a class="back" href="admin_panel.php">&larr; Back to Admin Panel</a></p>
        <h1>Add Product</h1>

        <div class="card">
            <?php if ($message): ?>
                <p class="message error"><?php echo $message; ?></p>
            <?php endif; ?>

            <form method="POST" action="add_product.php">
                <label>Product Name</label>
                <input type="text" name="name" required>
                <label>Description</label>
                <textarea name="description" required></textarea>
                <label>Price</label>
                <input type="number" step="0.01" name="price" required>
                <p class="stock-line">After creating the product, open <strong>Sizes / Stock</strong> in the admin panel to enter quantities for each size.</p>
                <label>Category</label>
                <select name="category" required>
                    <option value="MEN">MEN</option>
                    <option value="WOMEN">WOMEN</option>
                </select>
                <label>Image URL</label>
                <input type="text" name="image_url" placeholder="https://example.com/image.jpg">
                <button type="submit" class="btn" style="margin-top: 18px;">Add Product</button>
            </form>
        </div>
    </main>
</body>
</html>
