<?php
session_start();
require_once __DIR__ . '/auth_activity.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
require_once __DIR__ . '/ProductDAO.php';
require_once __DIR__ . '/cart_helpers.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = (int)($_POST['product_id'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 0);
    $size = strtoupper(trim($_POST['size'] ?? ''));
    $cart = normalizeShoppingCart();
    $product = getProductById($productId);

    if ($product && $quantity > 0 && in_array($size, availableClothingSizes(), true)) {
        $currentForSize = (int)($cart[$productId][$size] ?? 0);
        $totalForProduct = array_sum($cart[$productId] ?? []);
        $availableForSize = max(0, (int)$product['stock_quantity'] - $totalForProduct);
        $newTotal = min($currentForSize + $quantity, $currentForSize + $availableForSize);
        if ($newTotal > 0) $_SESSION['cart'][$productId][$size] = $newTotal;
    }
}

header('Location: shop.php');
exit();
?>
