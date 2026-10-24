<?php
// Page purpose: Admin reports: date-filtered sales chart, totals, status counts, top products, and exports.
session_start();
require_once __DIR__ . '/../auth_activity.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

if (($_SESSION['user_role'] ?? '') !== 'admin') {
    echo "<p style='font-family: Arial; margin: 60px;'>Access denied. Admins only. <a href='../dashboard.php'>Back to Dashboard</a></p>";
    exit();
}

require_once '../OrderDAO.php';
require_once __DIR__ . '/report_export_helpers.php';
require_once __DIR__ . '/../includes/sales_chart.php';

// Calendar range: default to the last 30 days and reject invalid dates.
$today = new DateTimeImmutable('today');
$defaultStart = $today->modify('-29 days');
$defaultEnd = $today;
$startInput = trim((string)($_GET['start_date'] ?? ''));
$endInput = trim((string)($_GET['end_date'] ?? ''));
$rangeError = '';

$parseReportDate = static function (string $value): ?DateTimeImmutable {
    if ($value === '') {
        return null;
    }
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    if (!$date || $date->format('Y-m-d') !== $value) {
        return null;
    }
    return $date;
};

$startDate = $startInput === '' ? $defaultStart : $parseReportDate($startInput);
$endDate = $endInput === '' ? $defaultEnd : $parseReportDate($endInput);

if ($startInput !== '' && $startDate === null) {
    $rangeError = 'Please enter a valid start date. Showing the default last 30 days instead.';
}
if ($endInput !== '' && $endDate === null) {
    $rangeError = 'Please enter a valid end date. Showing the default last 30 days instead.';
}
if ($rangeError === '' && $startDate > $endDate) {
    $rangeError = 'The start date must be on or before the end date. Showing the default last 30 days instead.';
}
if ($rangeError !== '' || $startDate === null || $endDate === null) {
    $startDate = $defaultStart;
    $endDate = $defaultEnd;
}

$startValue = $startDate->format('Y-m-d');
$endValue = $endDate->format('Y-m-d');
$rangeDays = (int)$startDate->diff($endDate)->days + 1;

// Keep the chart readable: daily for up to 31 days, weekly through 90 days,
// and monthly for longer ranges such as April through October.
$chartPeriod = $rangeDays <= 31 ? 'daily' : ($rangeDays <= 90 ? 'weekly' : 'monthly');
$chartPeriodLabel = ['daily' => 'day', 'weekly' => 'week', 'monthly' => 'month'][$chartPeriod];
$seriesLabel = ucfirst($chartPeriodLabel);
$rangeLabel = $startDate->format('M j, Y') . ' – ' . $endDate->format('M j, Y');

$salesSeries = getSalesSeries($chartPeriod, $startValue, $endValue);
$salesSummary = getSalesSummary($startValue, $endValue);
$statusCounts = getOrderCountsByStatus($startValue, $endValue);
$topProducts = getTopSellingProducts(5, $startValue, $endValue);

$chartTotalRevenue = 0.0;
$chartTotalOrders = 0;
foreach ($salesSeries as $point) {
    $chartTotalRevenue += (float)$point['revenue'];
    $chartTotalOrders += (int)$point['order_count'];
}

$cancelledOrders = 0;
foreach ($statusCounts as $row) {
    if (strcasecmp((string)$row['status'], 'Cancelled') === 0) {
        $cancelledOrders += (int)$row['count'];
    }
}
$averageOrderValue = (int)$salesSummary['order_count'] > 0
    ? (float)$salesSummary['total_revenue'] / (int)$salesSummary['order_count']
    : 0.0;

$exportQuery = 'start_date=' . urlencode($startValue) . '&end_date=' . urlencode($endValue);
$pdfExportUrl = '?export=pdf&' . $exportQuery;
$excelExportUrl = '?export=excel&' . $exportQuery;

/* Export the exact date range currently selected on the page. */
if (isset($_GET['export'])) {
    $exportType = strtolower((string)$_GET['export']);
    if (!in_array($exportType, ['pdf', 'excel'], true)) {
        http_response_code(400);
        exit('Invalid export format.');
    }

    $generatedAt = new DateTimeImmutable();
    $generatedLabel = $generatedAt->format('d M Y, h:i A');
    $filename = 'clothify_sales_report_' . $startValue . '_to_' . $endValue;

    try {
        if ($exportType === 'pdf') {
            $contents = buildSalesReportPdf(
                $salesSummary,
                $statusCounts,
                $topProducts,
                $generatedLabel,
                $rangeLabel,
                $salesSeries,
                $seriesLabel
            );
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $filename . '.pdf"');
        } else {
            $contents = buildSalesReportXlsx(
                $salesSummary,
                $statusCounts,
                $topProducts,
                $generatedLabel,
                $generatedAt,
                $rangeLabel,
                $salesSeries,
                $seriesLabel
            );
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . $filename . '.xlsx"');
        }

        header('Content-Length: ' . strlen($contents));
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        echo $contents;
        exit();
    } catch (Throwable $error) {
        error_log('Report export failed: ' . $error->getMessage());
        http_response_code(500);
        exit('The report could not be exported. Please try again.');
    }
}
?>
<!-- Page display: the HTML below renders the prepared data and forms. -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sales Report - Clothify</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .report-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            margin-bottom: 26px;
            flex-wrap: wrap;
        }
        .report-heading h1 { margin: 0; font-size: clamp(26px, 3vw, 34px); letter-spacing: -0.7px; }
        .report-subtitle { margin: 7px 0 0; color: var(--muted, #737373); font-size: 14px; }
        .export-wrap { position: relative; }
        .export-button {
            display: inline-flex; align-items: center; justify-content: center; gap: 10px;
            border: 1px solid #d7dce2; border-radius: 10px; background: #fff;
            color: #202938; padding: 11px 16px; font: inherit; font-weight: 600;
            cursor: pointer; box-shadow: 0 2px 5px rgba(20, 30, 50, .04);
            transition: background .18s, border-color .18s, transform .18s;
        }
        .export-button:hover { background: #f7f9fc; border-color: #aeb8c6; transform: translateY(-1px); }
        .export-button svg { width: 18px; height: 18px; }
        .export-menu {
            display: none; position: absolute; right: 0; top: calc(100% + 9px);
            z-index: 20; width: 250px; padding: 7px; background: #fff;
            border: 1px solid #e3e7ed; border-radius: 12px;
            box-shadow: 0 12px 32px rgba(20, 30, 50, .14);
        }
        .export-menu.open { display: block; }
        .export-menu-title { padding: 9px 10px 7px; color: #7a8493; font-size: 11px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; }
        .export-option {
            display: flex; width: 100%; align-items: center; gap: 11px; box-sizing: border-box;
            padding: 11px 10px; border: 0; border-radius: 8px; background: transparent;
            color: #202938; text-align: left; text-decoration: none; cursor: pointer; font: inherit;
        }
        .export-option:hover { background: #f3f6fa; }
        .export-option-icon { display: grid; place-items: center; width: 36px; height: 36px; border-radius: 9px; flex-shrink: 0; }
        .export-option-icon.pdf { background: #fff0ef; color: #c83d36; }
        .export-option-icon.excel { background: #eaf7ef; color: #18834b; }
        .export-option-icon svg { width: 19px; height: 19px; }
        .export-option strong { display: block; font-size: 14px; }
        .export-option small { display: block; margin-top: 3px; color: #7a8493; font-size: 12px; }

        .report-filter {
            display: flex; align-items: flex-end; justify-content: space-between; gap: 20px;
            flex-wrap: wrap; margin-bottom: 18px; padding: 18px 20px;
            border: 1px solid var(--border, #e5ded2); border-radius: 12px; background: #fff;
            box-shadow: 0 4px 18px rgba(20, 30, 50, .04);
        }
        .report-filter-copy span { display: block; color: #7a8493; font-size: 11px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; margin-bottom: 4px; }
        .report-filter-copy strong { display: block; font: 20px Georgia, serif; }
        .report-filter-copy small { display: block; margin-top: 4px; color: var(--muted, #737373); font-size: 12px; }
        .report-filter-fields { display: flex; align-items: flex-end; gap: 12px; flex-wrap: wrap; }
        .report-filter-fields label { margin: 0; color: var(--muted, #737373); font-size: 12px; font-weight: 600; }
        .report-filter-fields input[type="date"] { width: auto; min-width: 155px; margin-top: 5px; }
        .report-filter-fields .btn { margin: 0; }
        .report-filter-error { margin: 0 0 16px; }

        .report-summary-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; margin-bottom: 18px; }
        .report-summary-card { padding: 18px 20px; border: 1px solid var(--border, #e5ded2); border-radius: 10px; background: #fff; }
        .report-summary-card strong { display: block; color: var(--terracotta-dark, #8f4f16); font: 700 25px Georgia, serif; }
        .report-summary-card span { color: var(--muted, #737373); font-size: 14px; }
        .report-chart-card { margin-bottom: 22px; }

        .report-grid { display: grid; grid-template-columns: minmax(0, .85fr) minmax(0, 1.4fr); gap: 22px; align-items: start; }
        .report-grid .card { min-width: 0; margin: 0; padding: 24px; border: 1px solid #e6e9ef; border-radius: 14px; box-shadow: 0 4px 18px rgba(20, 30, 50, .04); }
        .report-card-title { margin: 0 0 18px; font-size: 18px; }
        .report-card-note { margin: -9px 0 14px; color: var(--muted, #737373); font-size: 12px; }
        .status-row { display: flex; justify-content: space-between; align-items: center; gap: 16px; padding: 13px 0; border-bottom: 1px solid var(--border, #e8ebef); font-size: 14px; }
        .status-row:last-child { border-bottom: none; }
        .status-count { display: inline-flex; justify-content: center; min-width: 34px; padding: 5px 10px; border-radius: 7px; background: #f2f5f9; color: #273449; font-weight: 700; }
        .report-grid table { width: 100%; border-collapse: collapse; }
        .report-grid th { padding: 12px 10px; background: #f7f8fa; color: #667085; text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: .06em; }
        .report-grid td { padding: 14px 10px; border-bottom: 1px solid var(--border, #e8ebef); font-size: 14px; }
        .report-grid tr:last-child td { border-bottom: 0; }
        .report-grid th:last-child, .report-grid td:last-child { text-align: right; }
        .revenue { font-weight: 650; white-space: nowrap; }
        .report-card-eyebrow { margin: 0 0 6px; color: #7a8493; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; }

        @media (max-width: 900px) {
            .report-summary-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 760px) {
            .report-grid { grid-template-columns: 1fr; }
            .report-heading { align-items: flex-start; }
            .export-wrap { width: 100%; }
            .export-button { width: 100%; }
            .export-menu { left: 0; right: auto; width: 100%; box-sizing: border-box; }
            .report-filter { align-items: stretch; }
            .report-filter-fields { width: 100%; }
            .report-filter-fields label { flex: 1 1 150px; }
            .report-filter-fields input[type="date"] { width: 100%; }
            .report-filter-fields .btn { flex: 1 1 auto; text-align: center; }
            .report-summary-grid { grid-template-columns: 1fr; }
        }
        @media print {
            body { background: #fff !important; color: #111 !important; }
            .report-heading { margin-bottom: 20px; }
            .export-wrap, .report-filter, .report-filter-error, nav, header, .navbar { display: none !important; }
            .page { max-width: 1100px; margin: 0 auto; padding: 20px !important; }
            .report-heading h1 { font-size: 28px; }
            .report-subtitle { color: #555 !important; }
            .report-summary-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; }
            .report-summary-card { padding: 12px; border: 1px solid #ddd; break-inside: avoid; }
            .report-summary-card strong { font-size: 18px; }
            .report-chart-card, .report-grid .card { box-shadow: none !important; border: 1px solid #ddd !important; break-inside: avoid; }
            .sales-chart-card { padding: 16px; }
            .sales-chart-scroll { overflow: visible !important; }
            .sales-chart { min-width: 0 !important; }
            .report-grid { grid-template-columns: 1fr 1.4fr; gap: 16px; }
            .report-grid th { background: #f0f0f0 !important; }
            * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body class="app-body">
    <?php $isAdminPage = true; require __DIR__ . '/../includes/navbar.php'; ?>

    <div class="page">
        <div class="report-heading">
            <div>
                <h1>Sales Report</h1>
                <p class="report-subtitle">Review sales for a date range, then export the same filtered report.</p>
            </div>
            <div class="export-wrap">
                <button class="export-button" id="exportButton" type="button" aria-haspopup="true" aria-expanded="false">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 3v12m-5-5 5 5 5-5"/><path d="M5 16v4h14v-4"/></svg>
                    Export report
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m7 10 5 5 5-5"/></svg>
                </button>
                <div class="export-menu" id="exportMenu" role="menu" aria-label="Choose export format">
                    <div class="export-menu-title">Export selected range</div>
                    <a class="export-option" href="<?php echo htmlspecialchars($pdfExportUrl, ENT_QUOTES, 'UTF-8'); ?>" role="menuitem" data-export-option>
                        <span class="export-option-icon pdf"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 3h8l4 4v14H6z"/><path d="M14 3v5h5M8 16h8M8 12h5"/></svg></span>
                        <span><strong>PDF document</strong><small>Download the selected date range</small></span>
                    </a>
                    <a class="export-option" href="<?php echo htmlspecialchars($excelExportUrl, ENT_QUOTES, 'UTF-8'); ?>" role="menuitem" data-export-option>
                        <span class="export-option-icon excel"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M13 3h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6"/><path d="M3 6h12v12H3zM6 9l6 6m0-6-6 6"/></svg></span>
                        <span><strong>Excel spreadsheet</strong><small>Formatted workbook with chart data</small></span>
                    </a>
                </div>
            </div>
        </div>

        <?php if ($rangeError !== ''): ?>
            <p class="message error report-filter-error"><?php echo htmlspecialchars($rangeError); ?></p>
        <?php endif; ?>

        <form class="report-filter" method="GET" action="admin_reports.php">
            <div class="report-filter-copy">
                <span>Report period</span>
                <strong><?php echo htmlspecialchars($rangeLabel); ?></strong>
                <small><?php echo number_format($rangeDays); ?> day<?php echo $rangeDays === 1 ? '' : 's'; ?> &middot; grouped by <?php echo htmlspecialchars($chartPeriodLabel); ?></small>
            </div>
            <div class="report-filter-fields">
                <label for="start_date">From
                    <input type="date" id="start_date" name="start_date" value="<?php echo htmlspecialchars($startValue, ENT_QUOTES, 'UTF-8'); ?>" max="<?php echo $today->format('Y-m-d'); ?>" required>
                </label>
                <label for="end_date">To
                    <input type="date" id="end_date" name="end_date" value="<?php echo htmlspecialchars($endValue, ENT_QUOTES, 'UTF-8'); ?>" max="<?php echo $today->format('Y-m-d'); ?>" required>
                </label>
                <button class="btn" type="submit">Apply range</button>
                <a class="btn btn-outline" href="admin_reports.php">Reset</a>
            </div>
        </form>

        <div class="report-summary-grid" aria-label="Sales summary for the selected range">
            <article class="report-summary-card">
                <strong>PHP <?php echo number_format((float)$salesSummary['total_revenue'], 2); ?></strong>
                <span>Net revenue</span>
            </article>
            <article class="report-summary-card">
                <strong><?php echo number_format((int)$salesSummary['order_count']); ?></strong>
                <span>Net orders</span>
            </article>
            <article class="report-summary-card">
                <strong><?php echo number_format($cancelledOrders); ?></strong>
                <span>Cancelled orders</span>
            </article>
            <article class="report-summary-card">
                <strong>PHP <?php echo number_format($averageOrderValue, 2); ?></strong>
                <span>Average order value</span>
            </article>
        </div>

        <div class="sales-chart-card report-chart-card">
            <div class="sales-chart-title">
                <div>
                    <h3>Sales</h3>
                    <p>Revenue by <?php echo htmlspecialchars($chartPeriodLabel); ?> &middot; <?php echo htmlspecialchars($rangeLabel); ?></p>
                </div>
                <span>Total PHP <?php echo number_format($chartTotalRevenue, 2); ?> &middot; <?php echo number_format($chartTotalOrders); ?> orders</span>
            </div>
            <div class="sales-chart-scroll">
                <?php renderSalesChart(
                    $salesSeries,
                    'Sales from ' . $rangeLabel,
                    'Bar chart of non-cancelled revenue by ' . $chartPeriodLabel . ' from ' . $rangeLabel . '.',
                    'report-sales-chart'
                ); ?>
            </div>
            <p class="sales-chart-footnote">
                Cancelled orders are excluded. Hover over a bar to see the total.
                <?php if ($chartTotalOrders === 0): ?>
                    No non-cancelled sales were recorded in this range.
                <?php else: ?>
                    Edge periods include only dates inside the selected range.
                <?php endif; ?>
            </p>
        </div>

        <div class="report-grid">
            <div class="card">
                <p class="report-card-eyebrow">Selected range</p>
                <h2 class="report-card-title">Orders by Status</h2>
                <p class="report-card-note">Status counts include cancelled orders; net totals exclude them.</p>
                <?php if (count($statusCounts) === 0): ?>
                    <p class="empty-state" style="padding: 10px 0;">No orders in this range.</p>
                <?php else: ?>
                    <?php foreach ($statusCounts as $row): ?>
                        <div class="status-row">
                            <span><?php echo htmlspecialchars(ucfirst(strtolower($row['status']))); ?></span>
                            <span class="status-count"><?php echo (int)$row['count']; ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="card">
                <p class="report-card-eyebrow">Product performance</p>
                <h2 class="report-card-title">Top Selling Products</h2>
                <p class="report-card-note">Ranked by units sold within the selected range.</p>
                <?php if (count($topProducts) === 0): ?>
                    <p class="empty-state" style="padding: 10px 0;">No product sales in this range.</p>
                <?php else: ?>
                    <table>
                        <tr>
                            <th>Product</th>
                            <th>Units Sold</th>
                            <th>Revenue</th>
                        </tr>
                        <?php foreach ($topProducts as $product): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                <td><?php echo (int)$product['total_sold']; ?></td>
                                <td class="revenue">PHP <?php echo number_format((float)$product['total_revenue'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script>
        (function () {
            const button = document.getElementById('exportButton');
            const menu = document.getElementById('exportMenu');
            if (!button || !menu) return;

            button.addEventListener('click', function (event) {
                event.stopPropagation();
                const isOpen = menu.classList.toggle('open');
                button.setAttribute('aria-expanded', String(isOpen));
            });

            document.addEventListener('click', function (event) {
                if (!menu.contains(event.target) && !button.contains(event.target)) {
                    menu.classList.remove('open');
                    button.setAttribute('aria-expanded', 'false');
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    menu.classList.remove('open');
                    button.setAttribute('aria-expanded', 'false');
                    button.focus();
                }
            });

            menu.querySelectorAll('a[data-export-option]').forEach(function (option) {
                option.addEventListener('click', function () {
                    menu.classList.remove('open');
                    button.setAttribute('aria-expanded', 'false');
                });
            });
        })();
    </script>
</body>
</html>
