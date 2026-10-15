<?php
session_start();
require_once __DIR__ . '/auth_activity.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

require_once 'ProductDAO.php';
require_once __DIR__ . '/cart_helpers.php';
require_once __DIR__ . '/SizeStockDAO.php';

$searchName = $_GET["name"] ?? "";
$searchCategory = $_GET["category"] ?? "ALL";
$minPrice = $_GET["min_price"] ?? "";
$maxPrice = $_GET["max_price"] ?? "";

$products = searchProducts($searchName, $searchCategory, $minPrice, $maxPrice);

normalizeShoppingCart();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Shop - Clothify</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .filters { display: flex; gap: 12px; margin-bottom: 24px; flex-wrap: wrap; align-items: flex-end; }
        .shop-page > h1 { text-align: center; }
        .shop-page .filters { justify-content: center; margin-left: auto; margin-right: auto; }
        .shop-page .grid { grid-template-columns: repeat(auto-fit, minmax(min(100%, 280px), 320px)); justify-content: center; }
        .filters .field { display: flex; flex-direction: column; }
        .filters label { margin: 0 0 4px 0; }
        .filters input, .filters select { width: auto; min-width: 120px; }
        .cart-badge { background: var(--terracotta); color: white; border-radius: 10px; padding: 1px 7px; font-size: 12px; margin-left: 4px; }
        .product-image { width: 100%; height: 180px; object-fit: cover; border-radius: 6px; margin: 0; background: var(--cream); }
        .product-image-link { position: relative; display: block; width: 100%; margin: 0 0 12px; padding: 0; border: 0; border-radius: 6px; overflow: hidden; cursor: zoom-in; background: transparent; }
        .product-description { white-space: pre-line; }
        .product-description.is-collapsed { display: -webkit-box; -webkit-box-orient: vertical; -webkit-line-clamp: 3; overflow: hidden; }
        .description-toggle { display: inline-block; margin: 2px 0 10px; padding: 8px 12px; border: 1px solid var(--border); border-radius: 5px; background: var(--cream); color: var(--terracotta-dark); cursor: pointer; font-size: 15px; font-weight: 600; }
        .description-toggle:hover { background: #f1e8dd; }
        .image-lightbox { position: relative; width: fit-content; max-width: 94vw; max-height: 94vh; padding: 12px; border: 0; border-radius: 10px; background: #fff; box-shadow: 0 18px 70px rgba(0,0,0,.35); overflow: visible; }
        .image-lightbox::backdrop { background: rgba(15,13,12,.82); }
        .image-lightbox img { display: block; max-width: min(90vw, 1200px); max-height: 88vh; object-fit: contain; border-radius: 6px; }
        .image-lightbox-close { position: absolute; z-index: 1; top: 18px; right: 18px; width: 42px; height: 42px; padding: 0; border: 0; border-radius: 50%; background: rgba(20,20,20,.85); color: white; font-size: 28px; line-height: 1; cursor: pointer; }
        .size-picker { margin: 12px 0 8px; }
        .size-picker label { margin: 0 0 5px; }
        .size-picker select { width: 100%; }
        .size-picker option:disabled { color: #999; }
    </style>
</head>
<body class="app-body">
    <?php $isAdminPage = false; require __DIR__ . '/includes/navbar.php'; ?>

    <main class="page shop-page">
        <h1 style="margin-bottom: 20px;">Shop</h1>

        <form method="GET" action="shop.php" class="filters">
            <div class="field">
                <label>Search</label>
                <input type="text" name="name" placeholder="Product name..." value="<?php echo htmlspecialchars($searchName); ?>">
            </div>
            <div class="field">
                <label>Category</label>
                <select name="category">
                    <option value="ALL" <?php if ($searchCategory === "ALL") echo "selected"; ?>>All</option>
                    <option value="MEN" <?php if ($searchCategory === "MEN") echo "selected"; ?>>Men</option>
                    <option value="WOMEN" <?php if ($searchCategory === "WOMEN") echo "selected"; ?>>Women</option>
                </select>
            </div>
            <div class="field">
                <label>Min Price</label>
                <input type="number" step="0.01" name="min_price" value="<?php echo htmlspecialchars($minPrice); ?>">
            </div>
            <div class="field">
                <label>Max Price</label>
                <input type="number" step="0.01" name="max_price" value="<?php echo htmlspecialchars($maxPrice); ?>">
            </div>
            <button type="submit" class="btn">Apply</button>
            <a class="btn btn-outline" href="shop.php">Clear</a>
        </form>

        <?php if (count($products) === 0): ?>
            <p class="empty-state">No products match your search.</p>
        <?php else: ?>
            <div class="grid">
                <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <?php
                        $sizeStock = getProductSizeStock($product['id']);
                        $totalSizeStock = array_sum($sizeStock);
                        ?>
                        <?php if (!empty($product["image_url"])): ?>
                            <button type="button" class="product-image-link" data-lightbox-image="<?php echo htmlspecialchars($product["image_url"], ENT_QUOTES, 'UTF-8'); ?>" data-lightbox-alt="<?php echo htmlspecialchars($product["name"], ENT_QUOTES, 'UTF-8'); ?>" aria-label="Enlarge image of <?php echo htmlspecialchars($product["name"], ENT_QUOTES, 'UTF-8'); ?>">
                                <img class="product-image" src="<?php echo htmlspecialchars($product["image_url"]); ?>" alt="<?php echo htmlspecialchars($product["name"]); ?>">
                            </button>
                        <?php endif; ?>

                        <h3><?php echo htmlspecialchars($product["name"]); ?></h3>
                        <?php $descriptionId = 'product-description-' . (int)$product['id']; ?>
                        <p class="product-description is-collapsed" id="<?php echo $descriptionId; ?>"><?php echo htmlspecialchars($product["description"]); ?></p>
                        <button type="button" class="description-toggle" data-description-toggle="<?php echo $descriptionId; ?>" aria-controls="<?php echo $descriptionId; ?>" aria-expanded="false">Show full description</button>
                        <p class="price">PHP <?php echo number_format($product["price"], 2); ?></p>
                        <p class="stock-line"><?php echo htmlspecialchars($product["category"]); ?> &middot; Stock: <?php echo $totalSizeStock; ?></p>

                        <?php if ($totalSizeStock > 0): ?>
                            <form method="POST" action="add_to_cart.php" style="margin-top: 10px;">
                                <input type="hidden" name="product_id" value="<?php echo $product["id"]; ?>">
                                <div class="size-picker">
                                    <label for="size-<?php echo $product["id"]; ?>">Size</label>
                                    <select name="size" id="size-<?php echo $product["id"]; ?>" required>
                                        <option value="" selected disabled>Choose a size</option>
                                        <?php foreach (availableClothingSizes() as $size): ?>
                                            <?php $sizeQuantity = $sizeStock[$size] ?? 0; ?>
                                            <option value="<?php echo htmlspecialchars($size); ?>" data-stock="<?php echo $sizeQuantity; ?>" <?php echo $sizeQuantity === 0 ? 'disabled' : ''; ?>><?php echo htmlspecialchars($size); ?><?php echo $sizeQuantity === 0 ? ' (Out of stock)' : ' (' . $sizeQuantity . ' available)'; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="stock-line">Choose an available size.</small>
                                </div>
                                <input type="number" name="quantity" value="1" min="1" max="1" style="width: 60px; display: inline-block;">
                                <button type="submit" class="btn">Add to Cart</button>
                            </form>
                        <?php else: ?>
                            <p class="out-of-stock">Out of Stock</p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
    <dialog class="image-lightbox" id="image-lightbox" aria-label="Enlarged product image">
        <button type="button" class="image-lightbox-close" aria-label="Close enlarged image">&times;</button>
        <img id="image-lightbox-content" src="" alt="">
    </dialog>
    <script>
        document.querySelectorAll('.size-picker select').forEach(function (select) {
            const quantityInput = select.closest('form').querySelector('input[name="quantity"]');
            function syncMaximum() {
                const option = select.options[select.selectedIndex];
                const available = Number(option && option.dataset.stock || 0);
                quantityInput.max = String(Math.max(1, available));
                if (available > 0 && Number(quantityInput.value) > available) quantityInput.value = String(available);
            }
            select.addEventListener('change', syncMaximum);
            syncMaximum();
        });
    </script>
    <script>
        document.querySelectorAll('[data-description-toggle]').forEach(function (button) {
            button.addEventListener('click', function () {
                const description = document.getElementById(button.dataset.descriptionToggle);
                const isCollapsed = description.classList.toggle('is-collapsed');
                button.textContent = isCollapsed ? 'Show full description' : 'Hide full description';
                button.setAttribute('aria-expanded', String(!isCollapsed));
            });
        });

        const lightbox = document.getElementById('image-lightbox');
        const lightboxImage = document.getElementById('image-lightbox-content');
        document.querySelectorAll('[data-lightbox-image]').forEach(function (button) {
            button.addEventListener('click', function () {
                lightboxImage.src = button.dataset.lightboxImage;
                lightboxImage.alt = button.dataset.lightboxAlt || 'Product image';
                lightbox.showModal();
            });
        });
        lightbox.querySelector('.image-lightbox-close').addEventListener('click', function () {
            lightbox.close();
        });
        lightbox.addEventListener('click', function (event) {
            if (event.target === lightbox) lightbox.close();
        });
    </script>
</body>
</html>
