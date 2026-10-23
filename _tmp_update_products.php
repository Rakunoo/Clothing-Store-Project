<?php
// Temporary cleanup: align a few product names and descriptions with the downloaded photography.
require_once __DIR__ . '/db.php';

$updates = [
    'Essential Slim-Fit Tee' => [
        'Essential Raglan Henley',
        'A soft cotton raglan henley with contrast sleeves, a button placket, and a relaxed everyday fit.',
    ],
    'Casual Slim-Fit Shirt' => [
        'Navy Henley Long Sleeve',
        'A soft navy long-sleeve henley with a button placket and a comfortable regular fit for casual layering.',
    ],
    'Sunlit Summer Dress' => [
        'Sunlit Summer Maxi Dress',
        'A flowing printed maxi dress with an easy drape and a standout pattern for warm-weather days.',
    ],
    'Slate Minimal Dress' => [
        'Stone Minimal Dress',
        'A sleeveless minimalist dress in a warm stone tone with a clean neckline and a relaxed column fit.',
    ],
    'Noir Evening Gown' => [
        'Noir Floral Gown',
        'A floor-length floral gown with a fluid drape and refined finish for evening occasions.',
    ],
    'Moto Corset Set' => [
        'Scarlet Corset Dress',
        'A sculpted strapless corset dress in a rich scarlet tone for a bold, occasion-ready look.',
    ],
    'Onyx Corset Set' => [
        'Onyx Corset Dress',
        'A structured black corset dress with a sculpted bodice and a clean, polished finish.',
    ],
    'Pea Coat Dress' => [
        'Polka-Dot Shirt Dress',
        'A polka-dot shirt dress with a defined waist, soft drape, and easy day-to-night styling.',
    ],
    'Ruby Tailored Suit' => [
        'Ruby Statement Dress',
        'A statement dress in deep ruby with a contrasting sleeve detail and a flattering tailored cut.',
    ],
    'Coastline Windbreaker' => [
        'Coastline Hooded Parka',
        'A hooded parka with a lightweight shell, drawstring waist, and practical pockets for rainy days.',
    ],
];

$conn = getConnection();
$conn->beginTransaction();
try {
    $update = $conn->prepare('UPDATE products SET name = ?, description = ? WHERE name = ?');
    foreach ($updates as $oldName => [$newName, $newDescription]) {
        $update->execute([$newName, $newDescription, $oldName]);
        echo ($update->rowCount() === 1 ? 'Updated: ' : 'Not found: ') . $oldName . ' -> ' . $newName . PHP_EOL;
    }
    $conn->commit();
} catch (Throwable $error) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    throw $error;
}
