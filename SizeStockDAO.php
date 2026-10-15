<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/cart_helpers.php';

function ensureProductSizeStockTable() {
    static $ready = false;
    if ($ready) return;

    $conn = getConnection();
    $conn->exec("CREATE TABLE IF NOT EXISTS product_size_stock (
        product_id INT NOT NULL,
        size VARCHAR(5) NOT NULL,
        quantity INT NOT NULL DEFAULT 0,
        PRIMARY KEY (product_id, size),
        CONSTRAINT fk_product_size_stock_product
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        CONSTRAINT chk_product_size_stock_quantity CHECK (quantity >= 0)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $ready = true;
}

// Read support: missing size rows are initialized to zero so they appear sold out.
function getProductSizeStock($productId) {
    ensureProductSizeStockTable();
    $conn = getConnection();
    $insert = $conn->prepare('INSERT IGNORE INTO product_size_stock (product_id, size, quantity) VALUES (?, ?, 0)');
    foreach (availableClothingSizes() as $size) $insert->execute([(int)$productId, $size]);

    $query = $conn->prepare('SELECT size, quantity FROM product_size_stock WHERE product_id = ?');
    $query->execute([(int)$productId]);
    $stock = array_fill_keys(availableClothingSizes(), 0);
    foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $row) $stock[$row['size']] = (int)$row['quantity'];
    return $stock;
}

function saveProductSizeStock($productId, array $quantities) {
    ensureProductSizeStockTable();
    $allowedSizes = availableClothingSizes();
    $conn = getConnection();
    $conn->beginTransaction();
    try {
        $upsert = $conn->prepare('INSERT INTO product_size_stock (product_id, size, quantity) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE quantity = VALUES(quantity)');
        $total = 0;
        foreach ($allowedSizes as $size) {
            $quantity = filter_var($quantities[$size] ?? 0, FILTER_VALIDATE_INT);
            if ($quantity === false || $quantity < 0) throw new InvalidArgumentException('Enter a whole number of zero or more for every size.');
            $upsert->execute([(int)$productId, $size, $quantity]);
            $total += $quantity;
        }
        $updateProduct = $conn->prepare('UPDATE products SET stock_quantity = ? WHERE id = ?');
        $updateProduct->execute([$total, (int)$productId]);
        $conn->commit();
        return true;
    } catch (Throwable $error) {
        if ($conn->inTransaction()) $conn->rollBack();
        throw $error;
    }
}

function reserveSizeStock($productId, $size, $quantity) {
    if (!in_array($size, availableClothingSizes(), true) || $quantity < 1) return false;
    ensureProductSizeStockTable();
    $conn = getConnection();
    $update = $conn->prepare('UPDATE product_size_stock SET quantity = quantity - ? WHERE product_id = ? AND size = ? AND quantity >= ?');
    $update->execute([(int)$quantity, (int)$productId, $size, (int)$quantity]);
    if ($update->rowCount() !== 1) return false;
    syncProductStockTotal($conn, $productId);
    return true;
}

function restoreSizeStock($productId, $size, $quantity) {
    if (!in_array($size, availableClothingSizes(), true) || $quantity < 1) return false;
    ensureProductSizeStockTable();
    $conn = getConnection();
    $update = $conn->prepare('INSERT INTO product_size_stock (product_id, size, quantity) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)');
    $update->execute([(int)$productId, $size, (int)$quantity]);
    syncProductStockTotal($conn, $productId);
    return true;
}

function syncProductStockTotal(PDO $conn, $productId) {
    $update = $conn->prepare('UPDATE products SET stock_quantity = (SELECT COALESCE(SUM(quantity), 0) FROM product_size_stock WHERE product_id = ?) WHERE id = ?');
    $update->execute([(int)$productId, (int)$productId]);
}
?>
