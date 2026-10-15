<?php
session_start();
require_once __DIR__ . '/auth_activity.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
require_once __DIR__ . '/UserDAO.php';
require_once __DIR__ . '/ProductDAO.php';
$stats = getUserStatistics();
$featuredProducts = array_slice(array_values(array_filter(getAllProducts(), function ($product) {
    return !empty($product['image_url']);
})), 0, 3);
$imageSettings = require __DIR__ . '/image-settings.php';
$fallbackImages = $imageSettings['dashboard_cards'];
$galleryCards = [];
foreach ($featuredProducts as $product) {
    $galleryCards[] = ['url' => $product['image_url'], 'title' => $product['name']];
}
foreach ($fallbackImages as $image) {
    if (count($galleryCards) >= 3) break;
    $galleryCards[] = $image;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard - Clothify</title><link rel="stylesheet" href="style.css"><link rel="stylesheet" href="image-settings.php">
</head>
<body class="app-body">
    <?php require __DIR__ . '/includes/navbar.php'; ?>
    <main class="page dashboard-page">
        <a class="dashboard-hero" href="shop.php" aria-label="Shop the Clothify collection">
            <div class="dashboard-hero-copy">
                <span class="eyebrow">CLOTHIFY · YOUR GO TO FASHION STORE   </span>
                <h1>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></h1>
                <p>Find pieces that feel like you. Explore the collection and make getting dressed the best part of your day.</p>
                <span class="dashboard-hero-cta">Shop Now <span aria-hidden="true">&rarr;</span></span>
            </div>
        </a>

        <section class="stat-grid" aria-label="System statistics">
            <div class="stat-card"><strong><?php echo number_format($stats['active']); ?></strong><span>Active users <small>(last 15 minutes)</small></span></div>
            <div class="stat-card"><strong><?php echo number_format($stats['registered']); ?></strong><span>Registered accounts</span></div>
            <div class="stat-card"><strong><?php echo number_format($stats['customers']); ?></strong><span>Customer accounts</span></div>
            <div class="stat-card"><strong><?php echo number_format($stats['administrators']); ?></strong><span>Administrators</span></div>
        </section>

        <section class="dashboard-collection" aria-labelledby="collection-heading">
            <div class="dashboard-section-heading"><div><span class="eyebrow">A LITTLE INSPIRATION</span><h2 id="collection-heading">Find your next favorite</h2></div><a href="shop.php">Explore the shop &rarr;</a></div>
            <div class="dashboard-image-grid">
                <?php foreach ($galleryCards as $image): ?>
                    <a class="dashboard-image-card" href="shop.php" aria-label="Shop the <?php echo htmlspecialchars($image['title']); ?> collection">
                        <img src="<?php echo htmlspecialchars($image['url']); ?>" alt="<?php echo htmlspecialchars($image['title']); ?>">
                        <span class="dashboard-image-caption"><?php echo htmlspecialchars($image['title']); ?><small>Shop collection &rarr;</small></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    </main>
</body>
</html>
