<?php
require_once 'db.php';

function getAllProducts() {
    $conn = getConnection();
    $sql = "SELECT * FROM products";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function searchProducts($name = "", $category = "", $minPrice = "", $maxPrice = "") {
    $conn = getConnection();
    $sql = "SELECT * FROM products WHERE 1=1";
    $params = [];

    if ($name !== "") {
        $sql .= " AND name LIKE ?";
        $params[] = "%" . $name . "%";
    }
    if ($category !== "" && $category !== "ALL") {
        $sql .= " AND category = ?";
        $params[] = $category;
    }
    if ($minPrice !== "") {
        $sql .= " AND price >= ?";
        $params[] = $minPrice;
    }
    if ($maxPrice !== "") {
        $sql .= " AND price <= ?";
        $params[] = $maxPrice;
    }

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getProductById($productId) {
    $conn = getConnection();
    $sql = "SELECT * FROM products WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    return $product ? $product : null;
}
function insertProduct($name, $description, $price, $stockQuantity, $category, $imageUrl = "") {
    $conn = getConnection();
    $sql = "INSERT INTO products (name, description, price, stock_quantity, category, image_url) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    return $stmt->execute([$name, $description, $price, $stockQuantity, $category, $imageUrl]);
}
function updateProduct($productId, $name, $description, $price, $stockQuantity, $category, $imageUrl = "") {
    $conn = getConnection();
    $sql = "UPDATE products SET name = ?, description = ?, price = ?, stock_quantity = ?, category = ?, image_url = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    return $stmt->execute([$name, $description, $price, $stockQuantity, $category, $imageUrl, $productId]);
}

function deleteProduct($productId) {
    $conn = getConnection();
    $sql = "DELETE FROM products WHERE id = ?";
    $stmt = $conn->prepare($sql);
    return $stmt->execute([$productId]);
}

function updateStock($productId, $quantityPurchased) {
    $conn = getConnection();
    $sql = "UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    return $stmt->execute([$quantityPurchased, $productId]);
}

function restoreStock($productId, $quantityToRestore) {
    $conn = getConnection();
    $sql = "UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    return $stmt->execute([$quantityToRestore, $productId]);
}
?>