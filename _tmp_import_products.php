<?php
// Temporary import: creates 24 casual clothing products with local, normalized product images.
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/SizeStockDAO.php';

$productDir = __DIR__ . '/assets/images/products';
if (!is_dir($productDir) && !mkdir($productDir, 0775, true)) {
    throw new RuntimeException('Could not create the product image directory.');
}

$products = [
    [
        'slug' => 'aspen-check-flannel-shirt',
        'name' => 'Aspen Check Flannel Shirt',
        'description' => 'A relaxed blue-and-black check flannel with a soft brushed finish, button cuffs, and a casual fit made for cool evenings.',
        'price' => 1299.00,
        'category' => 'MEN',
        'image' => 'https://cdn.dummyjson.com/product-images/mens-shirts/blue-&-black-check-shirt/1.webp',
    ],
    [
        'slug' => 'core-graphic-tee',
        'name' => 'Core Graphic Tee',
        'description' => 'An everyday cotton crew-neck tee with a clean graphic front and an easy regular fit for off-duty days.',
        'price' => 799.00,
        'category' => 'MEN',
        'image' => 'https://cdn.dummyjson.com/product-images/mens-shirts/gigabyte-aorus-men-tshirt/1.webp',
    ],
    [
        'slug' => 'rustic-plaid-overshirt',
        'name' => 'Rustic Plaid Overshirt',
        'description' => 'A brushed plaid overshirt with chest pockets and a roomy cut that layers easily over tees and hoodies.',
        'price' => 1499.00,
        'category' => 'MEN',
        'image' => 'https://cdn.dummyjson.com/product-images/mens-shirts/man-plaid-shirt/1.webp',
    ],
    [
        'slug' => 'everyday-short-sleeve-shirt',
        'name' => 'Everyday Short-Sleeve Shirt',
        'description' => 'A breathable short-sleeve button-down with a relaxed drape, perfect for warm days and laid-back weekends.',
        'price' => 1099.00,
        'category' => 'MEN',
        'image' => 'https://cdn.dummyjson.com/product-images/mens-shirts/man-short-sleeve-shirt/1.webp',
    ],
    [
        'slug' => 'heritage-check-shirt',
        'name' => 'Heritage Check Shirt',
        'description' => 'A classic check shirt with a structured collar, curved hem, and versatile fit for casual or smart-casual looks.',
        'price' => 1399.00,
        'category' => 'MEN',
        'image' => 'https://cdn.dummyjson.com/product-images/mens-shirts/men-check-shirt/1.webp',
    ],
    [
        'slug' => 'essential-slim-fit-tee',
        'name' => 'Essential Slim-Fit Tee',
        'description' => 'A premium cotton tee with a modern slim cut, ribbed neckline, and smooth hand feel for everyday wear.',
        'price' => 649.00,
        'category' => 'MEN',
        'image' => 'https://fakestoreapi.com/img/71-3HjGNDUL._AC_SY879._SX._UX._SY._UY_t.png',
    ],
    [
        'slug' => 'weekender-cotton-jacket',
        'name' => 'Weekender Cotton Jacket',
        'description' => 'A lightweight cotton jacket with utility pockets, a stand collar, and a clean silhouette for transitional weather.',
        'price' => 2499.00,
        'category' => 'MEN',
        'image' => 'https://fakestoreapi.com/img/71li-ujtlUL._AC_UX679_t.png',
    ],
    [
        'slug' => 'casual-slim-fit-shirt',
        'name' => 'Casual Slim-Fit Shirt',
        'description' => 'A soft-wash slim-fit shirt with a gentle stretch that moves easily from the office to the weekend.',
        'price' => 899.00,
        'category' => 'MEN',
        'image' => 'https://fakestoreapi.com/img/71YXzeOuslL._AC_UY879_t.png',
    ],
    [
        'slug' => 'azure-day-dress',
        'name' => 'Azure Day Dress',
        'description' => 'An easy day dress in a soft blue finish with a flattering silhouette and comfortable all-day drape.',
        'price' => 1499.00,
        'category' => 'WOMEN',
        'image' => 'https://cdn.dummyjson.com/product-images/tops/blue-frock/1.webp',
    ],
    [
        'slug' => 'sunlit-summer-dress',
        'name' => 'Sunlit Summer Dress',
        'description' => 'A light, airy summer dress with a relaxed shape and breezy finish for warm afternoons.',
        'price' => 1199.00,
        'category' => 'WOMEN',
        'image' => 'https://cdn.dummyjson.com/product-images/tops/girl-summer-dress/1.webp',
    ],
    [
        'slug' => 'slate-minimal-dress',
        'name' => 'Slate Minimal Dress',
        'description' => 'A clean-lined gray dress with a modern drape, minimal detailing, and an effortless everyday feel.',
        'price' => 1399.00,
        'category' => 'WOMEN',
        'image' => 'https://cdn.dummyjson.com/product-images/tops/gray-dress/1.webp',
    ],
    [
        'slug' => 'aria-short-dress',
        'name' => 'Aria Short Dress',
        'description' => 'A playful short dress with a flattering fit, soft lining, and just enough structure for day-to-night wear.',
        'price' => 1099.00,
        'category' => 'WOMEN',
        'image' => 'https://cdn.dummyjson.com/product-images/tops/short-frock/1.webp',
    ],
    [
        'slug' => 'tartan-wrap-dress',
        'name' => 'Tartan Wrap Dress',
        'description' => 'A tartan wrap dress with a defined waist and timeless pattern, styled for cooler days.',
        'price' => 1699.00,
        'category' => 'WOMEN',
        'image' => 'https://cdn.dummyjson.com/product-images/tops/tartan-dress/1.webp',
    ],
    [
        'slug' => 'noir-evening-gown',
        'name' => 'Noir Evening Gown',
        'description' => 'A floor-length black gown with a fluid drape and refined finish for evening occasions.',
        'price' => 3999.00,
        'category' => 'WOMEN',
        'image' => 'https://cdn.dummyjson.com/product-images/womens-dresses/black-women\'s-gown/1.webp',
    ],
    [
        'slug' => 'moto-corset-set',
        'name' => 'Moto Corset Set',
        'description' => 'A leather-look corset paired with a matching skirt for a bold, coordinated going-out look.',
        'price' => 2899.00,
        'category' => 'WOMEN',
        'image' => 'https://cdn.dummyjson.com/product-images/womens-dresses/corset-leather-with-skirt/1.webp',
    ],
    [
        'slug' => 'onyx-corset-set',
        'name' => 'Onyx Corset Set',
        'description' => 'A structured black corset and skirt set with a sculpted fit and polished finish.',
        'price' => 2699.00,
        'category' => 'WOMEN',
        'image' => 'https://cdn.dummyjson.com/product-images/womens-dresses/corset-with-black-skirt/1.webp',
    ],
    [
        'slug' => 'pea-coat-dress',
        'name' => 'Pea Coat Dress',
        'description' => 'A tailored pea-coat-inspired dress with clean lapels, a warm finish, and a sophisticated silhouette.',
        'price' => 1899.00,
        'category' => 'WOMEN',
        'image' => 'https://cdn.dummyjson.com/product-images/womens-dresses/dress-pea/1.webp',
    ],
    [
        'slug' => 'ruby-tailored-suit',
        'name' => 'Ruby Tailored Suit',
        'description' => 'A red-and-black tailored two-piece suit with a sharp cut for confident, occasion-ready styling.',
        'price' => 4499.00,
        'category' => 'WOMEN',
        'image' => 'https://cdn.dummyjson.com/product-images/womens-dresses/marni-red-&-black-suit/1.webp',
    ],
    [
        'slug' => 'alpine-3-in-1-jacket',
        'name' => 'Alpine 3-in-1 Jacket',
        'description' => 'A winter-ready 3-in-1 jacket with a removable inner layer, sealed seams, and practical pockets.',
        'price' => 2999.00,
        'category' => 'WOMEN',
        'image' => 'https://fakestoreapi.com/img/51Y5NI-I5jL._AC_UX679_t.png',
    ],
    [
        'slug' => 'rebel-moto-jacket',
        'name' => 'Rebel Moto Jacket',
        'description' => 'A hooded faux-leather moto jacket with asymmetric zip detailing and an edgy everyday finish.',
        'price' => 1999.00,
        'category' => 'WOMEN',
        'image' => 'https://fakestoreapi.com/img/81XH0e8fefL._AC_UY879_t.png',
    ],
    [
        'slug' => 'coastline-windbreaker',
        'name' => 'Coastline Windbreaker',
        'description' => 'A striped rain jacket with a packable hood, lightweight shell, and easy layering fit.',
        'price' => 1799.00,
        'category' => 'WOMEN',
        'image' => 'https://fakestoreapi.com/img/71HblAHs5xL._AC_UY879_-2t.png',
    ],
    [
        'slug' => 'boat-neck-everyday-top',
        'name' => 'Boat-Neck Everyday Top',
        'description' => 'A solid short-sleeve boat-neck top with a soft knit feel and a relaxed, flattering neckline.',
        'price' => 799.00,
        'category' => 'WOMEN',
        'image' => 'https://fakestoreapi.com/img/71z3kpMAYsL._AC_UY879_t.png',
    ],
    [
        'slug' => 'active-dry-tee',
        'name' => 'Active Dry Tee',
        'description' => 'A moisture-wicking active tee with a lightweight stretch knit for training, travel, and hot days.',
        'price' => 699.00,
        'category' => 'WOMEN',
        'image' => 'https://fakestoreapi.com/img/51eg55uWmdL._AC_UX679_t.png',
    ],
    [
        'slug' => 'weekend-cotton-tee',
        'name' => 'Weekend Cotton Tee',
        'description' => 'A casual cotton tee with a relaxed cut, durable stitching, and an easy everyday feel.',
        'price' => 749.00,
        'category' => 'WOMEN',
        'image' => 'https://fakestoreapi.com/img/61pHAEJ4NML._AC_UX679_t.png',
    ],
];

$sizePatterns = [
    [3, 5, 8, 6, 4, 2],
    [4, 6, 9, 7, 5, 3],
    [2, 4, 7, 8, 5, 3],
    [5, 7, 10, 8, 6, 4],
];

function installProductImage(string $url, string $targetPath): void
{
    $context = stream_context_create([
        'http' => [
            'header' => "User-Agent: ClothifyCatalog/1.0\r\n",
            'timeout' => 90,
        ],
    ]);

    $binary = @file_get_contents($url, false, $context);
    if ($binary === false) {
        throw new RuntimeException('Could not download ' . $url);
    }

    $source = @imagecreatefromstring($binary);
    if ($source === false) {
        throw new RuntimeException('Could not decode ' . $url);
    }

    $canvasWidth = 1000;
    $canvasHeight = 750;
    $padding = 42;
    $sourceWidth = imagesx($source);
    $sourceHeight = imagesy($source);
    $scale = min(
        ($canvasWidth - ($padding * 2)) / $sourceWidth,
        ($canvasHeight - ($padding * 2)) / $sourceHeight
    );
    $drawWidth = (int)round($sourceWidth * $scale);
    $drawHeight = (int)round($sourceHeight * $scale);
    $drawX = (int)(($canvasWidth - $drawWidth) / 2);
    $drawY = (int)(($canvasHeight - $drawHeight) / 2);

    $canvas = imagecreatetruecolor($canvasWidth, $canvasHeight);
    imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
    imagecopyresampled($canvas, $source, $drawX, $drawY, 0, 0, $drawWidth, $drawHeight, $sourceWidth, $sourceHeight);
    imagejpeg($canvas, $targetPath, 90);

    imagedestroy($canvas);
    imagedestroy($source);
}

$conn = getConnection();
$conn->beginTransaction();

try {
    $findProduct = $conn->prepare('SELECT id FROM products WHERE name = ?');
    $insertProduct = $conn->prepare('INSERT INTO products (name, description, price, stock_quantity, category, image_url) VALUES (?, ?, ?, ?, ?, ?)');
    $insertSize = $conn->prepare('INSERT INTO product_size_stock (product_id, size, quantity) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE quantity = VALUES(quantity)');
    $updateTotal = $conn->prepare('UPDATE products SET stock_quantity = ? WHERE id = ?');
    $sizes = availableClothingSizes();

    foreach ($products as $index => $product) {
        $slug = $product['slug'];
        $imagePath = $productDir . DIRECTORY_SEPARATOR . $slug . '.jpg';
        $relativeImage = 'assets/images/products/' . $slug . '.jpg';

        if (!is_file($imagePath)) {
            installProductImage($product['image'], $imagePath);
        }

        $findProduct->execute([$product['name']]);
        $existingId = $findProduct->fetchColumn();
        if ($existingId) {
            echo "Skipped existing product: {$product['name']} (ID {$existingId})" . PHP_EOL;
            continue;
        }

        $pattern = $sizePatterns[$index % count($sizePatterns)];
        $totalStock = array_sum($pattern);

        $insertProduct->execute([
            $product['name'],
            $product['description'],
            $product['price'],
            $totalStock,
            $product['category'],
            $relativeImage,
        ]);
        $productId = (int)$conn->lastInsertId();

        foreach ($sizes as $sizeIndex => $size) {
            $insertSize->execute([$productId, $size, $pattern[$sizeIndex]]);
        }
        $updateTotal->execute([$totalStock, $productId]);

        echo "Added #{$productId}: {$product['name']} ({$product['category']}, PHP " . number_format($product['price'], 2) . ', stock ' . $totalStock . ')' . PHP_EOL;
    }

    $conn->commit();
} catch (Throwable $error) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    throw $error;
}
