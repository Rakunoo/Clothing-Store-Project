<?php
require 'ProductDAO.php';
foreach (getAllProducts() as $p) {
    echo $p['id'] . ' | ' . $p['name'] . ' | ' . $p['category'] . ' | ' . $p['price'] . ' | ' . $p['stock_quantity'] . ' | ' . ($p['image_url'] ?? 'NULL') . PHP_EOL;
}
