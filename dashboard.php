<?php
// Page purpose: Shared customer/admin dashboard: prepares account data and recent sales; admins also see totals.
session_start();
require_once __DIR__ . '/auth_activity.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
require_once __DIR__ . '/UserDAO.php';
require_once __DIR__ . '/ProductDAO.php';
$stats = getUserStatistics();
$isAdmin = ($_SESSION['user_role'] ?? '') === 'admin';
require_once __DIR__ . '/OrderDAO.php';
$dailySales = getDailySalesLast30Days();
$chartMaxRevenue = max(1, ...array_column($dailySales, 'revenue'));
if ($isAdmin) {
    $salesSummary = getSalesSummary();
}
$featuredProducts = array_slice(array_values(array_filter(getAllProducts(), function ($product) {
    return !empty($product['image_url']);
})), 0, 1);
$imageSettings = require __DIR__ . '/image-settings.php';
$fallbackImages = $imageSettings['dashboard_cards'];
$galleryCards = [];
// Inspiration cards: the first uses one featured product; all remaining cards use fixed configured images.
foreach ($featuredProducts as $product) {
    $galleryCards[] = ['url' => $product['image_url'], 'title' => $product['name']];
}
foreach ($fallbackImages as $image) {
    if (count($galleryCards) >= 3) break;
    $galleryCards[] = $image;
}
?>
<!-- Page display: the HTML below renders the prepared data and forms. -->
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

        <!-- Store sales preview: all signed-in users can view recent daily sales; totals stay admin-only. -->
        <section class="admin-sales-overview" aria-labelledby="sales-overview-heading">
            <div class="dashboard-section-heading admin-sales-heading">
                <div><span class="eyebrow">A QUICK LOOK AT THE SHOP</span><h2 id="sales-overview-heading">Sales over the last 30 days</h2></div>
                <a href="shop.php">Browse the collection &rarr;</a>
            </div>
            <?php if ($isAdmin): ?>
                <div class="sales-summary-grid">
                    <article class="sales-summary-card">
                        <strong>PHP <?php echo number_format((float)$salesSummary['total_revenue'], 2); ?></strong>
                        <span>Total revenue <small>(excluding cancelled orders)</small></span>
                    </article>
                    <article class="sales-summary-card">
                        <strong><?php echo number_format((int)$salesSummary['order_count']); ?></strong>
                        <span>Total orders <small>(excluding cancelled orders)</small></span>
                    </article>
                </div>
            <?php endif; ?>
            <div class="sales-chart-card">
                <div class="sales-chart-title"><div><h3>Daily sales</h3><p>Revenue over the last 30 days</p></div><span>PHP</span></div>
                <div class="sales-chart-scroll">
                    <svg class="sales-chart" viewBox="0 0 960 330" role="img" aria-labelledby="sales-chart-title sales-chart-desc" preserveAspectRatio="xMidYMid meet">
                        <title id="sales-chart-title">Daily revenue for the last 30 days</title>
                        <desc id="sales-chart-desc">A bar chart showing non-cancelled order revenue per calendar day.</desc>
                        <?php for ($grid = 0; $grid <= 4; $grid++): $gridY = 250 - ($grid * 47.5); $gridValue = $chartMaxRevenue * $grid / 4; ?>
                            <line x1="72" y1="<?php echo $gridY; ?>" x2="940" y2="<?php echo $gridY; ?>" class="sales-chart-gridline" />
                            <text x="62" y="<?php echo $gridY + 5; ?>" text-anchor="end" class="sales-chart-y-label"><?php echo number_format($gridValue, 0); ?></text>
                        <?php endfor; ?>
                        <?php foreach ($dailySales as $index => $daySale):
                            $barHeight = ((float)$daySale['revenue'] / $chartMaxRevenue) * 190;
                            $barX = 78 + ($index * 28.5);
                            $barY = 250 - $barHeight;
                        ?>
                            <rect x="<?php echo number_format($barX, 1, '.', ''); ?>" y="<?php echo number_format($barY, 1, '.', ''); ?>" width="17" height="<?php echo number_format($barHeight, 1, '.', ''); ?>" rx="4" class="sales-chart-bar">
                                <title><?php echo date('M j', strtotime($daySale['day'])); ?>: PHP <?php echo number_format((float)$daySale['revenue'], 2); ?></title>
                            </rect>
                            <?php if ($index % 5 === 0 || $index === 29): ?>
                                <text x="<?php echo number_format($barX + 8.5, 1, '.', ''); ?>" y="280" text-anchor="middle" class="sales-chart-x-label"><?php echo date('M j', strtotime($daySale['day'])); ?></text>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </svg>
                </div>
                <p class="sales-chart-footnote">Cancelled orders are excluded. Hover over a bar to see that day’s total.</p>
            </div>
        </section>

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
