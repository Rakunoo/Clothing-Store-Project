<?php
session_start();
require_once __DIR__ . '/../auth_activity.php';

// Access control: only store administrators can change per-size inventory.
if (!isset($_SESSION['user_id'])) { header('Location: ../login.php'); exit(); }
if (($_SESSION['user_role'] ?? '') !== 'admin') {
    echo "<p style='font-family: Arial; margin: 60px;'>Access denied. Admins only. <a href='../dashboard.php'>Back to Dashboard</a></p>";
    exit();
}

// Page data: load the chosen item and use the shared inventory functions.
require_once __DIR__ . '/../ProductDAO.php';
require_once __DIR__ . '/../SizeStockDAO.php';
$productId = (int)($_GET['id'] ?? $_POST['product_id'] ?? 0);
$product = $productId > 0 ? getProductById($productId) : null;
$message = '';
$messageType = 'error';

// Form handling: save the quantity for every listed clothing size.
if ($product && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        saveProductSizeStock($productId, $_POST['stock'] ?? []);
        $message = 'Size quantities saved. The product total has been updated.';
        $messageType = 'success';
    } catch (Throwable $error) {
        $message = $error instanceof InvalidArgumentException ? $error->getMessage() : 'Could not save size quantities. Please try again.';
    }
}
$sizes = $product ? getProductSizeStock($productId) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Size Stock - Clothify</title><link rel="stylesheet" href="../style.css">
    <style>
        .size-stock-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(145px,1fr)); gap:14px; }
        .size-stock-field { padding:14px; border:1px solid var(--border); border-radius:7px; background:#fff; }
        .size-stock-field label { margin:0 0 6px; }
        .size-stock-field input { margin:0; }
        .size-stock-note { color:var(--muted); margin:8px 0 18px; }
    </style>
</head>
<body class="app-body">
    <?php $isAdminPage = true; require __DIR__ . '/../includes/navbar.php'; ?>
    <main class="page narrow admin-centered">
        <p><a class="back" href="admin_panel.php">&larr; Back to Admin Panel</a></p>
        <?php if (!$product): ?>
            <h1>Product not found</h1>
        <?php else: ?>
            <h1>Size Stock: <?php echo htmlspecialchars($product['name']); ?></h1>
            <div class="card">
                <?php if ($message): ?><p class="message <?php echo htmlspecialchars($messageType); ?>"><?php echo htmlspecialchars($message); ?></p><?php endif; ?>
                <p class="size-stock-note">Set how many units are available in each size. A size with quantity 0 is disabled in the shop. Existing shared stock starts at 0 per size until you enter its actual distribution.</p>
                <form method="POST" action="manage_size_stock.php?id=<?php echo $productId; ?>">
                    <input type="hidden" name="product_id" value="<?php echo $productId; ?>">
                    <div class="size-stock-grid">
                        <?php foreach ($sizes as $size => $quantity): ?>
                            <div class="size-stock-field">
                                <label for="stock-<?php echo htmlspecialchars($size); ?>">Size <?php echo htmlspecialchars($size); ?></label>
                                <input id="stock-<?php echo htmlspecialchars($size); ?>" type="number" name="stock[<?php echo htmlspecialchars($size); ?>]" value="<?php echo (int)$quantity; ?>" min="0" step="1" required>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="submit" class="btn" style="margin-top:18px">Save Size Quantities</button>
                </form>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
