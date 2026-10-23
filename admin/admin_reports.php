<?php
// Page purpose: Admin reports: gathers and displays sales totals, status counts, and top products.
session_start();
require_once __DIR__ . '/../auth_activity.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit();
}

if ($_SESSION["user_role"] !== "admin") {
    echo "<p style='font-family: Arial; margin: 60px;'>Access denied. Admins only. <a href='../dashboard.php'>Back to Dashboard</a></p>";
    exit();
}

require_once '../OrderDAO.php';

$statusCounts = getOrderCountsByStatus();
$topProducts = getTopSellingProducts(5);

/* Excel-compatible CSV export. PDF export is handled by the browser print dialog. */
if (isset($_GET['export'])) {
    $exportType = strtolower((string) $_GET['export']);

    if ($exportType === 'excel') {
        // Styled Excel-compatible HTML workbook. Unlike CSV, this preserves formatting,
        // readable date/time text, and sensible column sizing when opened in Excel.
        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment; filename="clothify_sales_report_' . date('Y-m-d') . '.xls"');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        $generatedAt = date('d M Y, h:i A');
        echo "\xEF\xBB\xBF";
        ?>
        <!DOCTYPE html>
        <html xmlns:o="urn:schemas-microsoft-com:office:office"
              xmlns:x="urn:schemas-microsoft-com:office:excel"
              xmlns="http://www.w3.org/TR/REC-html40">
        <head>
            <meta charset="UTF-8">
            <meta name="ProgId" content="Excel.Sheet">
            <meta name="Generator" content="Clothify Reports">
            <title>Clothify Sales Report</title>
            <!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Sales Report</x:Name><x:WorksheetOptions><x:DisplayGridlines>False</x:DisplayGridlines><x:FitToPage/><x:Print><x:FitWidth>1</x:FitWidth><x:FitHeight>0</x:FitHeight></x:Print></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->
            <style>
                body { font-family: Calibri, Arial, sans-serif; color: #243247; }
                table { border-collapse: collapse; table-layout: auto; width: auto; }
                td, th { padding: 9px 12px; vertical-align: middle; white-space: nowrap; }
                .title { background: #172b4d; color: #ffffff; font-size: 18pt; font-weight: bold; padding: 16px 14px; }
                .subtitle { background: #eaf0f7; color: #52627a; font-size: 10pt; padding: 10px 14px; }
                .section { background: #24466c; color: #ffffff; font-size: 12pt; font-weight: bold; padding: 11px 12px; }
                .headers { background: #e7edf5; color: #172b4d; font-weight: bold; border-bottom: 2px solid #24466c; }
                .cell { border-bottom: 1px solid #e1e7ef; }
                .alternate { background: #f5f8fc; }
                .number { text-align: right; }
                .currency { text-align: right; color: #176747; mso-number-format: '"PHP "#,##0.00'; }
                .count { text-align: right; mso-number-format: '0'; }
                .note { color: #68778b; font-size: 9pt; }
            </style>
        </head>
        <body>
        <table>
            <colgroup><col><col><col></colgroup>
            <tr><td class="title" colspan="3">CLOTHIFY | SALES REPORT</td></tr>
            <tr><td class="subtitle" colspan="3">Generated on: <?php echo htmlspecialchars($generatedAt, ENT_QUOTES, 'UTF-8'); ?></td></tr>
            <tr><td colspan="3"></td></tr>
            <tr><td class="section" colspan="2">ORDERS BY STATUS</td><td></td></tr>
            <tr><th class="headers">Order Status</th><th class="headers">Order Count</th><td></td></tr>
            <?php if (count($statusCounts) === 0): ?>
                <tr><td class="cell" colspan="2">No orders available.</td><td></td></tr>
            <?php else: ?>
                <?php foreach ($statusCounts as $i => $row): ?>
                    <tr>
                        <td class="cell <?php echo $i % 2 ? 'alternate' : ''; ?>"><?php echo htmlspecialchars(ucfirst(strtolower((string) $row['status'])), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td class="cell count <?php echo $i % 2 ? 'alternate' : ''; ?>"><?php echo (int) $row['count']; ?></td>
                        <td></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            <tr><td colspan="3"></td></tr>
            <tr><td class="section" colspan="3">TOP SELLING PRODUCTS</td></tr>
            <tr><th class="headers">Product</th><th class="headers">Units Sold</th><th class="headers">Revenue (PHP)</th></tr>
            <?php if (count($topProducts) === 0): ?>
                <tr><td class="cell" colspan="3">No sales available.</td></tr>
            <?php else: ?>
                <?php foreach ($topProducts as $i => $product): ?>
                    <tr>
                        <td class="cell <?php echo $i % 2 ? 'alternate' : ''; ?>"><?php echo htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td class="cell count <?php echo $i % 2 ? 'alternate' : ''; ?>"><?php echo (int) $product['total_sold']; ?></td>
                        <td class="cell currency <?php echo $i % 2 ? 'alternate' : ''; ?>"><?php echo number_format((float) $product['total_revenue'], 2, '.', ''); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            <tr><td colspan="3"></td></tr>
            <tr><td class="note" colspan="3">Confidential business report • Clothify</td></tr>
        </table>
        </body>
        </html>
        <?php
        exit();
    }

    http_response_code(400);
    exit('Invalid export format.');
}
?>
<!-- Page display: the HTML below renders the prepared data and forms. -->
<!DOCTYPE html>
<html>
<head>
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
            z-index: 20; width: 230px; padding: 7px; background: #fff;
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
        .report-grid { display: grid; grid-template-columns: minmax(0, .85fr) minmax(0, 1.4fr); gap: 22px; align-items: start; }
        .report-grid .card { min-width: 0; margin: 0; padding: 24px; border: 1px solid #e6e9ef; border-radius: 14px; box-shadow: 0 4px 18px rgba(20, 30, 50, .04); }
        .report-card-title { margin: 0 0 18px; font-size: 18px; }
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
        @media (max-width: 760px) {
            .report-grid { grid-template-columns: 1fr; }
            .report-heading { align-items: flex-start; }
            .export-wrap { width: 100%; }
            .export-button { width: 100%; }
            .export-menu { left: 0; right: auto; width: 100%; box-sizing: border-box; }
        }
        @media print {
            body { background: #fff !important; color: #111 !important; }
            .report-heading { margin-bottom: 24px; }
            .export-wrap, nav, header, .navbar { display: none !important; }
            .page { max-width: 1100px; margin: 0 auto; padding: 20px !important; }
            .report-heading h1 { font-size: 28px; }
            .report-subtitle { color: #555 !important; }
            .report-grid .card { padding: 18px; border-radius: 8px; }
            .report-grid { grid-template-columns: 1fr 1.4fr; gap: 16px; }
            .report-grid .card { box-shadow: none !important; border: 1px solid #ddd !important; break-inside: avoid; }
            .report-grid th { background: #f0f0f0 !important; }
        }
    </style>
</head>
<body class="app-body">
    <?php $isAdminPage = true; require __DIR__ . '/../includes/navbar.php'; ?>

    <div class="page">
        <div class="report-heading">
            <div>
                <h1>Sales Report</h1>
                <p class="report-subtitle">Review order performance and your best-selling products.</p>
            </div>
            <?php if (true): ?>
            <div class="export-wrap">
                <button class="export-button" id="exportButton" type="button" aria-haspopup="true" aria-expanded="false">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 3v12m-5-5 5 5 5-5"/><path d="M5 16v4h14v-4"/></svg>
                    Export report
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m7 10 5 5 5-5"/></svg>
                </button>
                <div class="export-menu" id="exportMenu" role="menu" aria-label="Choose export format">
                    <div class="export-menu-title">Choose file format</div>
                    <button class="export-option" type="button" role="menuitem" id="exportPdf">
                        <span class="export-option-icon pdf"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 3h8l4 4v14H6z"/><path d="M14 3v5h5M8 16h8M8 12h5"/></svg></span>
                        <span><strong>PDF document</strong><small>Print-ready report</small></span>
                    </button>
                    <a class="export-option" href="?export=excel" role="menuitem">
                        <span class="export-option-icon excel"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M13 3h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6"/><path d="M3 6h12v12H3zM6 9l6 6m0-6-6 6"/></svg></span>
                        <span><strong>Excel spreadsheet</strong><small>Formatted Excel file with auto-sized columns</small></span>
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="report-grid">
        <div class="card">
            <p class="report-card-eyebrow">Overview</p>
            <h2 class="report-card-title">Orders by Status</h2>
            <?php if (count($statusCounts) === 0): ?>
                <p class="empty-state" style="padding: 10px 0;">No orders yet.</p>
            <?php else: ?>
                <?php foreach ($statusCounts as $row): ?>
                    <div class="status-row">
                        <span><?php echo htmlspecialchars(ucfirst(strtolower($row["status"]))); ?></span>
                        <span class="status-count"><?php echo (int) $row["count"]; ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="card">
            <p class="report-card-eyebrow">Product performance</p>
            <h2 class="report-card-title">Top Selling Products</h2>
            <?php if (count($topProducts) === 0): ?>
                <p class="empty-state" style="padding: 10px 0;">No sales yet.</p>
            <?php else: ?>
                <table>
                    <tr>
                        <th>Product</th>
                        <th>Units Sold</th>
                        <th>Revenue</th>
                    </tr>
                    <?php foreach ($topProducts as $product): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($product["name"]); ?></td>
                            <td><?php echo $product["total_sold"]; ?></td>
                            <td class="revenue">PHP <?php echo number_format((float) $product["total_revenue"], 2); ?></td>
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

            document.getElementById('exportPdf').addEventListener('click', function (event) {
                event.preventDefault();
                menu.classList.remove('open');
                button.setAttribute('aria-expanded', 'false');
                if (window.confirm('Export this Sales Report as a PDF? The print dialog will open. Choose “Save as PDF” as the destination.')) {
                    window.print();
                }
            });

            menu.querySelector('a[href="?export=excel"]').addEventListener('click', function (event) {
                if (!window.confirm('Download this formatted Sales Report for Excel?')) {
                    event.preventDefault();
                }
            });
        })();
    </script>
</body>
</html>
