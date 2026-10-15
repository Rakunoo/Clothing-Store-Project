<?php
function availableClothingSizes() {
    return ['XS', 'S', 'M', 'L', 'XL', '2XL'];
}

function normalizeShoppingCart() {
    $cart = $_SESSION['cart'] ?? [];
    $normalized = [];
    foreach ($cart as $productId => $sizes) {
        if (is_array($sizes)) {
            foreach ($sizes as $size => $quantity) {
                $size = strtoupper((string)$size);
                $quantity = (int)$quantity;
                if (in_array($size, availableClothingSizes(), true) && $quantity > 0) {
                    $normalized[$productId][$size] = ($normalized[$productId][$size] ?? 0) + $quantity;
                }
            }
        } else {
            $quantity = (int)$sizes;
            if ($quantity > 0) $normalized[$productId]['M'] = $quantity;
        }
    }
    $_SESSION['cart'] = $normalized;
    return $normalized;
}
?>
