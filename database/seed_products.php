<?php
// CLI seeder: adds the 30-item Clothify clothing catalog with local product images.
// Run from the project root with: php database/seed_products.php
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Run this seeder from the command line.');
}

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../SizeStockDAO.php';

$productDir = __DIR__ . '/../assets/images/products';

$products = [
    // MEN
    ['aspen-check-flannel-shirt', 'Aspen Check Flannel Shirt', 'A relaxed blue-and-black check flannel with a soft brushed finish, button cuffs, and a casual fit made for cool evenings.', 1299.00, 'MEN'],
    ['core-graphic-tee', 'Core Graphic Tee', 'An everyday cotton crew-neck tee with a clean graphic front and an easy regular fit for off-duty days.', 799.00, 'MEN'],
    ['rustic-plaid-overshirt', 'Rustic Plaid Overshirt', 'A brushed plaid overshirt with chest pockets and a roomy cut that layers easily over tees and hoodies.', 1499.00, 'MEN'],
    ['everyday-short-sleeve-shirt', 'Everyday Short-Sleeve Shirt', 'A breathable short-sleeve button-down with a relaxed drape, perfect for warm days and laid-back weekends.', 1099.00, 'MEN'],
    ['heritage-check-shirt', 'Heritage Check Shirt', 'A classic check shirt with a structured collar, curved hem, and versatile fit for casual or smart-casual looks.', 1399.00, 'MEN'],
    ['essential-slim-fit-tee', 'Essential Slim-Fit Tee', 'A premium cotton tee with a modern slim cut, ribbed neckline, and smooth hand feel for everyday wear.', 649.00, 'MEN'],
    ['weekender-cotton-jacket', 'Weekender Cotton Jacket', 'A lightweight cotton jacket with utility pockets, a stand collar, and a clean silhouette for transitional weather.', 2499.00, 'MEN'],
    ['casual-slim-fit-shirt', 'Casual Slim-Fit Shirt', 'A soft-wash slim-fit shirt with a gentle stretch that moves easily from the office to the weekend.', 899.00, 'MEN'],
    ['blue-check-classic-shirt', 'Blue Check Classic Shirt', 'A crisp blue-and-black check shirt with a structured collar, button front, and a clean regular fit for everyday layering.', 1299.00, 'MEN'],
    ['aorus-tech-graphic-tee', 'Aorus Tech Graphic Tee', 'A soft cotton crew-neck tee with a monochrome tech-inspired graphic and contrasting black trim.', 899.00, 'MEN'],
    ['crimson-buffalo-check-shirt', 'Crimson Buffalo Check Shirt', 'A bold red-and-black buffalo check shirt with a brushed finish and easy button-down styling.', 1399.00, 'MEN'],
    ['blue-hibiscus-camp-shirt', 'Blue Hibiscus Camp Shirt', 'A relaxed short-sleeve camp shirt in a blue hibiscus print, cut for warm days and easy weekends.', 1199.00, 'MEN'],
    ['evergreen-plaid-shirt', 'Evergreen Plaid Shirt', 'A green-and-teal plaid shirt with a soft hand feel, chest pocket, and versatile casual fit.', 1349.00, 'MEN'],

    // WOMEN
    ['azure-day-dress', 'Azure Day Dress', 'An easy day dress in a soft blue finish with a flattering silhouette and comfortable all-day drape.', 1499.00, 'WOMEN'],
    ['sunlit-summer-dress', 'Sunlit Summer Dress', 'A light, airy summer dress with a relaxed shape and breezy finish for warm afternoons.', 1199.00, 'WOMEN'],
    ['slate-minimal-dress', 'Slate Minimal Dress', 'A clean-lined gray dress with a modern drape, minimal detailing, and an effortless everyday feel.', 1399.00, 'WOMEN'],
    ['aria-short-dress', 'Aria Short Dress', 'A playful short dress with a flattering fit, soft lining, and just enough structure for day-to-night wear.', 1099.00, 'WOMEN'],
    ['tartan-wrap-dress', 'Tartan Wrap Dress', 'A tartan wrap dress with a defined waist and timeless pattern, styled for cooler days.', 1699.00, 'WOMEN'],
    ['noir-evening-gown', 'Noir Evening Gown', 'A floor-length black gown with a fluid drape and refined finish for evening occasions.', 3999.00, 'WOMEN'],
    ['moto-corset-set', 'Moto Corset Set', 'A leather-look corset paired with a matching skirt for a bold, coordinated going-out look.', 2899.00, 'WOMEN'],
    ['onyx-corset-set', 'Onyx Corset Set', 'A structured black corset and skirt set with a sculpted fit and polished finish.', 2699.00, 'WOMEN'],
    ['pea-coat-dress', 'Pea Coat Dress', 'A tailored pea-coat-inspired dress with clean lapels, a warm finish, and a sophisticated silhouette.', 1899.00, 'WOMEN'],
    ['ruby-tailored-suit', 'Ruby Tailored Suit', 'A red-and-black tailored two-piece suit with a sharp cut for confident, occasion-ready styling.', 4499.00, 'WOMEN'],
    ['alpine-3-in-1-jacket', 'Alpine 3-in-1 Jacket', 'A winter-ready 3-in-1 jacket with a removable inner layer, sealed seams, and practical pockets.', 2999.00, 'WOMEN'],
    ['rebel-moto-jacket', 'Rebel Moto Jacket', 'A hooded faux-leather moto jacket with asymmetric zip detailing and an edgy everyday finish.', 1999.00, 'WOMEN'],
    ['coastline-windbreaker', 'Coastline Windbreaker', 'A striped rain jacket with a packable hood, lightweight shell, and easy layering fit.', 1799.00, 'WOMEN'],
    ['boat-neck-everyday-top', 'Boat-Neck Everyday Top', 'A solid short-sleeve boat-neck top with a soft knit feel and a relaxed, flattering neckline.', 799.00, 'WOMEN'],
    ['active-dry-tee', 'Active Dry Tee', 'A moisture-wicking active tee with a lightweight stretch knit for training, travel, and hot days.', 699.00, 'WOMEN'],
    ['weekend-cotton-tee', 'Weekend Cotton Tee', 'A casual cotton tee with a relaxed cut, durable stitching, and an easy everyday feel.', 749.00, 'WOMEN'],
    ['blue-polka-dot-dress', 'Blue Polka-Dot Dress', 'A playful blue polka-dot dress with a fitted bodice, short sleeves, and a full pleated skirt.', 1499.00, 'WOMEN'],
];

$sizePatterns = [
    [3, 5, 8, 6, 4, 2],
    [4, 6, 9, 7, 5, 3],
    [2, 4, 7, 8, 5, 3],
    [5, 7, 10, 8, 6, 4],
    [3, 6, 9, 9, 6, 3],
];

$sizes = availableClothingSizes();
ensureProductSizeStockTable();

$conn = getConnection();
$conn->beginTransaction();

try {
    $findProduct = $conn->prepare('SELECT id FROM products WHERE name = ?');
    $insertProduct = $conn->prepare('INSERT INTO products (name, description, price, stock_quantity, category, image_url) VALUES (?, ?, ?, ?, ?, ?)');
    $insertSize = $conn->prepare('INSERT INTO product_size_stock (product_id, size, quantity) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE quantity = VALUES(quantity)');

    $added = 0;
    $skipped = 0;

    foreach ($products as $index => $product) {
        [$slug, $name, $description, $price, $category] = $product;
        $relativeImage = 'assets/images/products/' . $slug . '.jpg';
        $absoluteImage = $productDir . '/' . $slug . '.jpg';

        if (!is_file($absoluteImage)) {
            throw new RuntimeException('Missing product image: ' . $absoluteImage);
        }

        $findProduct->execute([$name]);
        $existingId = $findProduct->fetchColumn();
        if ($existingId) {
            echo "Skipped existing product: {$name} (ID {$existingId})" . PHP_EOL;
            $skipped++;
            continue;
        }

        $pattern = $sizePatterns[$index % count($sizePatterns)];
        $totalStock = array_sum($pattern);

        $insertProduct->execute([$name, $description, $price, $totalStock, $category, $relativeImage]);
        $productId = (int)$conn->lastInsertId();

        foreach ($sizes as $sizeIndex => $size) {
            $insertSize->execute([$productId, $size, $pattern[$sizeIndex]]);
        }

        echo "Added #{$productId}: {$name} ({$category}, PHP " . number_format($price, 2) . ', stock ' . $totalStock . ')' . PHP_EOL;
        $added++;
    }

    // Keep the legacy product total equal to the sum of its per-size stock.
    $conn->exec('UPDATE products p SET stock_quantity = (SELECT COALESCE(SUM(s.quantity), 0) FROM product_size_stock s WHERE s.product_id = p.id)');

    $conn->commit();
    echo PHP_EOL . "Catalog complete: {$added} added, {$skipped} already present." . PHP_EOL;
} catch (Throwable $error) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    fwrite(STDERR, 'Import failed: ' . $error->getMessage() . PHP_EOL);
    exit(1);
}
