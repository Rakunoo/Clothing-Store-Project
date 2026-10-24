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
require_once __DIR__ . '/OrderDAO.php';
require_once __DIR__ . '/includes/sales_chart.php';

$stats = getUserStatistics();
$isAdmin = ($_SESSION['user_role'] ?? '') === 'admin';

// Sales report period: daily (last 7 days), weekly (last 12 weeks), or monthly (last 12 months).
$salesPeriods = ['daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly'];
$salesPeriod = isset($_GET['period']) ? strtolower((string)$_GET['period']) : 'daily';
if (!isset($salesPeriods[$salesPeriod])) {
    $salesPeriod = 'daily';
}

$today = new DateTimeImmutable('today');
if ($salesPeriod === 'weekly') {
    $currentWeekStart = $today->modify('-' . ((int)$today->format('N') - 1) . ' days');
    $salesRangeStart = $currentWeekStart->modify('-11 weeks');
    $salesRangeEnd = $today;
    $salesRangeLabel = $salesRangeStart->format('M j, Y') . ' – ' . $salesRangeEnd->format('M j, Y');
    $salesSubtitle = 'Revenue by week · ' . $salesRangeLabel;
    $chartTitle = 'Sales for the last 12 weeks';
    $salesPeriodNoun = 'week';
    $salesPartialNote = ' The current week is still in progress.';
} elseif ($salesPeriod === 'monthly') {
    $salesRangeStart = $today->modify('first day of this month')->modify('-11 months');
    $salesRangeEnd = $today;
    $salesRangeLabel = $salesRangeStart->format('M Y') . ' – ' . $salesRangeEnd->format('M Y');
    $salesSubtitle = 'Revenue by month · ' . $salesRangeLabel;
    $chartTitle = 'Sales for the last 12 months';
    $salesPeriodNoun = 'month';
    $salesPartialNote = ' The current month is still in progress.';
} else {
    $salesRangeStart = $today->modify('-6 days');
    $salesRangeEnd = $today;
    $salesRangeLabel = $salesRangeStart->format('M j, Y') . ' – ' . $salesRangeEnd->format('M j, Y');
    $salesSubtitle = 'Revenue by day · ' . $salesRangeLabel;
    $chartTitle = 'Sales for the last 7 days';
    $salesPeriodNoun = 'day';
    $salesPartialNote = '';
}

$salesStartValue = $salesRangeStart->format('Y-m-d');
$salesEndValue = $salesRangeEnd->format('Y-m-d');
$salesSeries = getSalesSeries($salesPeriod, $salesStartValue, $salesEndValue);
$chartTotalRevenue = 0.0;
$chartTotalOrders = 0;
foreach ($salesSeries as $salesPoint) {
    $chartTotalRevenue += (float)$salesPoint['revenue'];
    $chartTotalOrders += (int)$salesPoint['order_count'];
}
$salesSummary = $isAdmin ? getSalesSummary($salesStartValue, $salesEndValue) : null;
$chartDescription = 'Bar chart of non-cancelled revenue by ' . $salesPeriodNoun . ' from ' . $salesRangeLabel . '.';

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

        <!-- Store sales preview: all signed-in users can switch between daily, weekly, and monthly views. -->
        <section class="admin-sales-overview" aria-labelledby="sales-overview-heading">
            <div class="dashboard-section-heading admin-sales-heading">
                <div><span class="eyebrow">A QUICK LOOK AT THE SHOP</span><h2 id="sales-overview-heading">Sales overview</h2></div>
                <a href="shop.php">Browse the collection &rarr;</a>
            </div>

            <div class="sales-period-bar">
                <p class="sales-period-note">Showing <strong><?php echo htmlspecialchars($salesPeriods[$salesPeriod]); ?></strong> totals &middot; <?php echo htmlspecialchars($salesRangeLabel); ?></p>
                <nav class="sales-period-tabs" aria-label="Sales report period">
                    <?php foreach ($salesPeriods as $periodKey => $periodLabel): ?>
                        <a href="dashboard.php?period=<?php echo urlencode($periodKey); ?>"<?php echo $salesPeriod === $periodKey ? ' aria-current="page"' : ''; ?>><?php echo htmlspecialchars($periodLabel); ?></a>
                    <?php endforeach; ?>
                </nav>
            </div>

            <?php if ($isAdmin): ?>
                <div class="sales-summary-grid">
                    <article class="sales-summary-card">
                        <strong>PHP <?php echo number_format((float)$salesSummary['total_revenue'], 2); ?></strong>
                        <span>Revenue <small>(selected period, excluding cancelled orders)</small></span>
                    </article>
                    <article class="sales-summary-card">
                        <strong><?php echo number_format((int)$salesSummary['order_count']); ?></strong>
                        <span>Orders <small>(selected period, excluding cancelled orders)</small></span>
                    </article>
                </div>
            <?php endif; ?>

            <div class="sales-chart-card">
                <div class="sales-chart-title">
                    <div><h3>Sales</h3><p><?php echo htmlspecialchars($salesSubtitle); ?></p></div>
                    <span>Total PHP <?php echo number_format($chartTotalRevenue, 2); ?> &middot; <?php echo number_format($chartTotalOrders); ?> orders</span>
                </div>
                <div class="sales-chart-scroll">
                    <?php renderSalesChart($salesSeries, $chartTitle, $chartDescription, 'dashboard-sales-chart'); ?>
                </div>
                <p class="sales-chart-footnote">Cancelled orders are excluded. Hover over a bar to see that <?php echo htmlspecialchars($salesPeriodNoun); ?>&rsquo;s total.<?php echo htmlspecialchars($salesPartialNote); ?></p>
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
