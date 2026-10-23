<?php
session_start();
require_once __DIR__ . '/auth_activity.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
require_once __DIR__ . '/ProductDAO.php';
require_once __DIR__ . '/cart_helpers.php';

$cart = normalizeShoppingCart();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['update_id'] ?? $_POST['remove_id'] ?? 0);
    $size = strtoupper(trim($_POST['size'] ?? 'M'));
    if ($id > 0 && in_array($size, availableClothingSizes(), true)) {
        if (isset($_POST['remove_id'])) {
            unset($_SESSION['cart'][$id][$size]);
            if (empty($_SESSION['cart'][$id])) unset($_SESSION['cart'][$id]);
        } elseif (isset($_POST['update_id'])) {
            $oldSize = strtoupper(trim($_POST['old_size'] ?? $size));
            $quantity = (int)($_POST['quantity'] ?? 0);
            $product = getProductById($id);
            if ($product && in_array($oldSize, availableClothingSizes(), true)) {
                $otherQuantity = array_sum($_SESSION['cart'][$id] ?? []) - (int)($_SESSION['cart'][$id][$oldSize] ?? 0);
                $existingAtNewSize = $oldSize === $size ? 0 : (int)($_SESSION['cart'][$id][$size] ?? 0);
                $quantity = min(max(0, $quantity), max(0, (int)$product['stock_quantity'] - $otherQuantity - $existingAtNewSize));
                unset($_SESSION['cart'][$id][$oldSize]);
                if ($quantity > 0) $_SESSION['cart'][$id][$size] = $existingAtNewSize + $quantity;
                if (empty($_SESSION['cart'][$id])) unset($_SESSION['cart'][$id]);
            }
        }
    }
    header('Location: cart.php');
    exit();
}

$cartItems = [];
$total = 0;
foreach ($cart as $productId => $sizes) {
    $product = getProductById($productId);
    if (!$product) continue;
    foreach ($sizes as $size => $quantity) {
        $subtotal = $product['price'] * $quantity;
        $cartItems[] = ['product' => $product, 'size' => $size, 'quantity' => $quantity, 'subtotal' => $subtotal];
        $total += $subtotal;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Cart - Clothify</title><link rel="stylesheet" href="style.css"><style>
    .qty-form { display:flex; gap:7px; align-items:center; flex-wrap:wrap; }
    .qty-form input { width:68px; padding:7px; }
    .qty-form select { width:88px; padding:7px; }
    .remove-form button { background:none; border:0; color:var(--danger); cursor:pointer; text-decoration:underline; padding:0; }
    .cart-total { text-align:right; font-size:18px; margin:20px 0; }
    .product-cell { display:flex; align-items:center; }
</style></head>
<body class="app-body">
    <?php $isAdminPage = false; require __DIR__ . '/includes/navbar.php'; ?>
    <main class="page">
        <h1 style="margin-bottom:24px">Your Cart</h1>
        <?php if (count($cartItems) === 0): ?>
            <p class="empty-state">Your cart is empty. <a href="shop.php">Go shopping</a>.</p>
        <?php else: ?>
            <div class="card" style="padding:0"><table>
                <tr><th style="padding-left:20px">Product</th><th>Size</th><th>Price</th><th>Quantity</th><th>Subtotal</th><th style="padding-right:20px"></th></tr>
                <?php foreach ($cartItems as $item): ?>
                    <tr>
                        <td style="padding-left:20px"><div class="product-cell"><?php if (!empty($item['product']['image_url'])): ?><img class="mini-thumb" src="<?php echo htmlspecialchars($item['product']['image_url']); ?>" alt=""><?php endif; ?><?php echo htmlspecialchars($item['product']['name']); ?></div></td>
                        <td><?php echo htmlspecialchars($item['size']); ?></td>
                        <td>PHP <?php echo number_format($item['product']['price'], 2); ?></td>
                        <td><form method="POST" action="cart.php" class="qty-form">
                            <input type="hidden" name="update_id" value="<?php echo (int)$item['product']['id']; ?>">
                            <input type="hidden" name="old_size" value="<?php echo htmlspecialchars($item['size']); ?>">
                            <select name="size" aria-label="Size for <?php echo htmlspecialchars($item['product']['name']); ?>">
                                <?php foreach (availableClothingSizes() as $size): ?><option value="<?php echo htmlspecialchars($size); ?>" <?php echo $size === $item['size'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($size); ?></option><?php endforeach; ?>
                            </select>
                            <input type="number" name="quantity" value="<?php echo (int)$item['quantity']; ?>" min="1" max="<?php echo (int)$item['product']['stock_quantity']; ?>">
                            <button type="submit" class="btn btn-outline">Update</button>
                        </form></td>
                        <td>PHP <?php echo number_format($item['subtotal'], 2); ?></td>
                        <td style="padding-right:20px"><form method="POST" action="cart.php" class="remove-form"><input type="hidden" name="remove_id" value="<?php echo (int)$item['product']['id']; ?>"><input type="hidden" name="size" value="<?php echo htmlspecialchars($item['size']); ?>"><button type="submit">Remove</button></form></td>
                    </tr>
                <?php endforeach; ?>
            </table></div>
            <p class="cart-total">Total: PHP <?php echo number_format($total, 2); ?></p>
            <div style="text-align:right"><a class="btn" href="checkout.php">Proceed to Checkout</a></div>
        <?php endif; ?>
    </main>
</body>
</html>
