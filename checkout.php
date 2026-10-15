<?php
session_start();
require_once __DIR__ . '/auth_activity.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

require_once 'ProductDAO.php';
require_once 'OrderDAO.php';
require_once 'OrderItemDAO.php';
require_once __DIR__ . '/cart_helpers.php';

$cart = normalizeShoppingCart();
if (count($cart) === 0) {
    header("Location: cart.php");
    exit();
}

$message = "";

$cartItems = [];
$total = 0;
$quantityByProduct = [];
// Checkout summary: count each unit, including multiple quantities of one size.
$itemCount = 0;
foreach ($cart as $productId => $sizes) {
    $product = getProductById($productId);
    if (!$product) continue;
    foreach ($sizes as $size => $quantity) {
        $subtotal = $product['price'] * $quantity;
        $cartItems[] = ['product' => $product, 'size' => $size, 'quantity' => $quantity, 'subtotal' => $subtotal];
        $total += $subtotal;
        $itemCount += (int)$quantity;
        $quantityByProduct[$productId] = ($quantityByProduct[$productId] ?? 0) + $quantity;
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $userId = $_SESSION["user_id"];

    $stockProblem = "";
    foreach ($quantityByProduct as $productId => $quantity) {
        $product = getProductById($productId);
        if ($product && $product['stock_quantity'] < $quantity) {
            $stockProblem = "Not enough stock for " . htmlspecialchars($product['name']) . ".";
            break;
        }
    }

    if ($stockProblem) {
        $message = $stockProblem;
    } else {
        $newOrderId = insertOrder($userId, $total, "Pending");

        if ($newOrderId) {
            foreach ($cartItems as $item) {
                insertOrderItem($newOrderId, $item['product']['id'], $item['quantity'], $item['product']['price'], $item['size']);
                updateStock($item["product"]["id"], $item["quantity"]);
            }

            $_SESSION["cart"] = [];
            header("Location: my_orders.php");
            exit();
        } else {
            $message = "Order failed. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Checkout - Clothify</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .checkout-item { margin: 14px 0; padding-bottom: 14px; border-bottom: 1px solid var(--border); }
        .checkout-item-left { display: grid; grid-template-columns: 108px minmax(0, 1fr); align-items: start; gap: 14px; min-width: 0; }
        .checkout-product-image { width: 108px; height: 128px; overflow: hidden; border-radius: 8px; background: var(--cream); }
        .checkout-product-image img { display: block; width: 100%; height: 100%; object-fit: cover; }
        .checkout-image-placeholder { display: grid; width: 100%; height: 100%; place-items: center; padding: 8px; color: var(--muted); font-size: 12px; text-align: center; }
        .checkout-item-details { display: grid; align-content: start; gap: 7px; min-width: 0; }
        .checkout-item-details strong { overflow-wrap: anywhere; }
        .checkout-item-price { margin-top: 3px; font-weight: 600; }
        .checkout-cart-count { margin: 0 0 10px; color: var(--muted); font-size: 14px; }
        .checkout-image-button { display: block; width: 100%; height: 100%; padding: 0; border: 0; background: transparent; cursor: zoom-in; }
        .image-lightbox { position: relative; width: fit-content; max-width: 94vw; max-height: 94vh; padding: 12px; border: 0; border-radius: 10px; background: #fff; box-shadow: 0 18px 70px rgba(0,0,0,.35); overflow: visible; }
        .image-lightbox::backdrop { background: rgba(15,13,12,.82); }
        .image-lightbox img { display: block; max-width: min(90vw, 1200px); max-height: 88vh; object-fit: contain; border-radius: 6px; }
        .image-lightbox-close { position: absolute; z-index: 1; top: 18px; right: 18px; width: 42px; height: 42px; padding: 0; border: 0; border-radius: 50%; background: rgba(20,20,20,.85); color: white; font-size: 28px; line-height: 1; cursor: pointer; }
        @media (max-width: 680px) { .checkout-item-left { grid-template-columns: 88px minmax(0, 1fr); gap: 12px; } .checkout-product-image { width: 88px; height: 104px; } }
    </style>
</head>
<body class="app-body">
    <?php $isAdminPage = false; require __DIR__ . '/includes/navbar.php'; ?>

    <div class="page narrow">
        <p><a class="back" href="cart.php">&larr; Back to Cart</a></p>
        <h1>Checkout</h1>

        <?php if ($message): ?>
            <p class="message error"><?php echo $message; ?></p>
        <?php endif; ?>

        <div class="card">
            <p class="checkout-cart-count">Items in cart: <?php echo $itemCount; ?></p>
            <?php foreach ($cartItems as $item): ?>
                <div class="checkout-item">
                    <div class="checkout-item-left">
                        <div class="checkout-product-image">
                            <?php if (!empty($item["product"]["image_url"])): ?>
                                <button type="button" class="checkout-image-button" data-lightbox-image="<?php echo htmlspecialchars($item["product"]["image_url"], ENT_QUOTES, 'UTF-8'); ?>" data-lightbox-alt="<?php echo htmlspecialchars($item["product"]["name"], ENT_QUOTES, 'UTF-8'); ?>" aria-label="Enlarge image of <?php echo htmlspecialchars($item["product"]["name"], ENT_QUOTES, 'UTF-8'); ?>">
                                    <img src="<?php echo htmlspecialchars($item["product"]["image_url"], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($item["product"]["name"], ENT_QUOTES, 'UTF-8'); ?>">
                                </button>
                            <?php else: ?>
                                <span class="checkout-image-placeholder">No product image</span>
                            <?php endif; ?>
                        </div>
                        <div class="checkout-item-details">
                            <strong><?php echo htmlspecialchars($item['product']['name']); ?></strong>
                            <span>Size: <?php echo htmlspecialchars($item['size']); ?></span>
                            <span>Quantity: <?php echo (int)$item['quantity']; ?></span>
                            <span class="checkout-item-price">PHP <?php echo number_format($item["subtotal"], 2); ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            <hr style="border-color: var(--border); margin: 14px 0;">
            <p style="display: flex; justify-content: space-between; font-weight: bold;">
                <span>Total</span>
                <span>PHP <?php echo number_format($total, 2); ?></span>
            </p>

            <form method="POST" action="checkout.php">
                <button type="submit" class="btn btn-block" style="margin-top: 16px;">Place Order</button>
            </form>
        </div>
    </div>
    <!-- Image preview: clicking a product photo opens it in the same large viewer used by the shop. -->
    <dialog class="image-lightbox" id="image-lightbox" aria-label="Enlarged product image">
        <button type="button" class="image-lightbox-close" aria-label="Close enlarged image">&times;</button>
        <img id="image-lightbox-content" src="" alt="">
    </dialog>
    <script>
        const lightbox = document.getElementById('image-lightbox');
        const lightboxImage = document.getElementById('image-lightbox-content');
        document.querySelectorAll('[data-lightbox-image]').forEach(function (button) {
            button.addEventListener('click', function () {
                lightboxImage.src = button.dataset.lightboxImage;
                lightboxImage.alt = button.dataset.lightboxAlt || 'Product image';
                lightbox.showModal();
            });
        });
        lightbox.querySelector('.image-lightbox-close').addEventListener('click', function () { lightbox.close(); });
        lightbox.addEventListener('click', function (event) { if (event.target === lightbox) lightbox.close(); });
    </script>
</body>
</html>
