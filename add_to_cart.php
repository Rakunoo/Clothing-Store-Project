<?php
session_start();
require_once __DIR__ . '/auth_activity.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
require_once __DIR__ . '/ProductDAO.php';
require_once __DIR__ . '/cart_helpers.php';
require_once __DIR__ . '/SizeStockDAO.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = (int)($_POST['product_id'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 0);
    $size = strtoupper(trim($_POST['size'] ?? ''));
    $cart = normalizeShoppingCart();
    $product = getProductById($productId);

    // Cart validation: accept only a listed size with remaining stock.
    if ($product && $quantity > 0 && in_array($size, availableClothingSizes(), true)) {
        $currentForSize = (int)($cart[$productId][$size] ?? 0);
        $sizeStock = getProductSizeStock($productId);
        $newTotal = min($currentForSize + $quantity, (int)($sizeStock[$size] ?? 0));
        if ($newTotal > 0) $_SESSION['cart'][$productId][$size] = $newTotal;
    }
}

header('Location: shop.php');
exit();
?>
