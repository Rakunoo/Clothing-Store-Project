<?php
// Page purpose: Admin catalog: lists products and provides links to product-management actions.
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

require_once '../ProductDAO.php';

$allProducts = getAllProducts();
$filterId = trim((string)($_GET['id'] ?? ''));
$filterCategory = trim((string)($_GET['category'] ?? ''));
$minPrice = trim((string)($_GET['min_price'] ?? ''));
$maxPrice = trim((string)($_GET['max_price'] ?? ''));
$products = array_values(array_filter($allProducts, static function ($product) use ($filterId, $filterCategory, $minPrice, $maxPrice) {
    if ($filterId !== '' && (string)$product['id'] !== $filterId) return false;
    if ($filterCategory !== '' && strcasecmp((string)$product['category'], $filterCategory) !== 0) return false;
    if ($minPrice !== '' && (float)$product['price'] < (float)$minPrice) return false;
    if ($maxPrice !== '' && (float)$product['price'] > (float)$maxPrice) return false;
    return true;
}));
$categories = array_values(array_unique(array_filter(array_map(static fn($p) => trim((string)$p['category']), $allProducts))));
sort($categories, SORT_NATURAL | SORT_FLAG_CASE);
$filterQuery = http_build_query(array_filter(['id'=>$filterId, 'category'=>$filterCategory, 'min_price'=>$minPrice, 'max_price'=>$maxPrice], static fn($v) => $v !== ''));

if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    $filename = 'clothify_clothing_inventory_' . date('Y-m-d') . '.xls';
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-store, no-cache, must-revalidate');

    $escape = static function ($value) {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    };
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>
        body{font-family:Calibri,Arial,sans-serif;color:#1f2937}
        table{border-collapse:collapse;width:100%}
        td,th{border:1px solid #d7dee8;padding:9px 12px}
        .title{background:#142b4a;color:#fff;font-size:20px;font-weight:bold;padding:15px}
        .meta{background:#eef3f8;color:#46566b}
        th{background:#24558a;color:#fff;text-align:left;font-weight:bold}
        .num{text-align:right;mso-number-format:"\\#\\,\\#\\#0.00"}
        .qty{text-align:center}
        .alt{background:#f3f6fa}
    </style></head><body><table>';
    echo '<tr><td class="title" colspan="5">CLOTHIFY | CLOTHING INVENTORY</td></tr>';
    echo '<tr><td class="meta" colspan="5">Generated: ' . $escape(date('d M Y, h:i A')) . ' | Products exported: ' . count($products) . '</td></tr>';
    if ($filterId !== '' || $filterCategory !== '' || $minPrice !== '' || $maxPrice !== '') echo '<tr><td colspan="5">Filters: ID=' . $escape($filterId ?: 'Any') . '; Category=' . $escape($filterCategory ?: 'Any') . '; Min price=' . $escape($minPrice ?: 'Any') . '; Max price=' . $escape($maxPrice ?: 'Any') . '</td></tr>'; 
    echo '<tr><th>ID</th><th>Clothing / Product</th><th>Price (PHP)</th><th>Stock</th><th>Category</th></tr>';
    foreach ($products as $i => $product) {
        $rowClass = ($i % 2 === 1) ? ' class="alt"' : '';
        echo '<tr' . $rowClass . '><td>' . $escape($product['id']) . '</td><td>' . $escape($product['name']) . '</td><td class="num">' . number_format((float)$product['price'], 2, '.', ',') . '</td><td class="qty">' . $escape($product['stock_quantity']) . '</td><td>' . $escape($product['category']) . '</td></tr>';
    }
    echo '</table></body></html>';
    exit();
}
?>
<!-- Page display: the HTML below renders the prepared data and forms. -->
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Panel - Clothify</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .inventory-filters{margin-bottom:18px;padding:20px;border:1px solid #e3e8ef;border-radius:12px}
        .filter-grid{display:grid;grid-template-columns:repeat(4,minmax(130px,1fr)) auto;gap:14px;align-items:end}
        .filter-grid label{display:flex;flex-direction:column;gap:6px;color:#46566b;font-size:12px;font-weight:700}
        .filter-grid input,.filter-grid select{width:100%;box-sizing:border-box;padding:10px 11px;border:1px solid #d7dee8;border-radius:8px;background:#fff;color:#202938;font:inherit;font-weight:400}
        .filter-actions{display:flex;align-items:center;gap:10px;white-space:nowrap}
        .filter-summary{margin:12px 0 0;color:#788496;font-size:12px}
        @media(max-width:900px){.filter-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.filter-actions{grid-column:1/-1}}
        .admin-heading{display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;margin-bottom:24px}
        .admin-heading h1{margin:0}
        .admin-heading-actions{display:flex;gap:10px;align-items:center;flex-wrap:wrap}
        .export-wrap{position:relative}
        .export-button{display:inline-flex;align-items:center;gap:9px;padding:10px 15px;border:1px solid #d7dce2;border-radius:9px;background:#fff;color:#202938;font:inherit;font-weight:600;cursor:pointer}
        .export-button:hover{background:#f5f7fa}
        .export-menu{display:none;position:absolute;right:0;top:calc(100% + 8px);z-index:50;width:225px;padding:7px;background:#fff;border:1px solid #e2e7ee;border-radius:12px;box-shadow:0 12px 30px #172b4d20}
        .export-menu.open{display:block}
        .export-menu-title{padding:8px 10px;color:#788496;font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase}
        .export-option{display:flex;align-items:center;gap:10px;width:100%;box-sizing:border-box;padding:11px 10px;border:0;border-radius:8px;background:transparent;color:#202938;text-decoration:none;text-align:left;font:inherit;cursor:pointer}
        .export-option:hover{background:#f1f5f9}
        .export-option strong{display:block;font-size:14px}.export-option small{display:block;margin-top:3px;color:#788496;font-size:12px}
        @media(max-width:600px){.admin-heading-actions{width:100%}.admin-heading-actions>a,.admin-heading-actions .export-button{width:100%;justify-content:center}.export-wrap{width:100%}.export-menu{width:100%;box-sizing:border-box}}
        @media print{.admin-heading-actions,.actions-column,nav,.navbar,header{display:none!important}.page{max-width:none!important}.card{box-shadow:none!important;border:1px solid #ddd!important}}
    </style>
</head>
<body class="app-body">
    <?php $isAdminPage = true; require __DIR__ . '/../includes/navbar.php'; ?>

    <div class="page">
        <div class="admin-heading">
            <div>
                <h1>Clothing Inventory</h1>
                <p style="margin:7px 0 0;color:#778293;font-size:14px;">Manage products, prices, categories, and available stock.</p>
            </div>
            <div class="admin-heading-actions">
                <div class="export-wrap">
                    <button class="export-button" id="exportButton" type="button" aria-haspopup="true" aria-expanded="false">↓ Export inventory ▾</button>
                    <div class="export-menu" id="exportMenu" role="menu">
                        <div class="export-menu-title">Choose file format</div>
                        <button class="export-option" type="button" id="exportPdf" role="menuitem"><span>📄</span><span><strong>PDF document</strong><small>Print or save as PDF</small></span></button>
                        <a class="export-option" href="?export=excel&amp;<?php echo htmlspecialchars($filterQuery, ENT_QUOTES, 'UTF-8'); ?>" role="menuitem"><span>📊</span><span><strong>Excel spreadsheet</strong><small>Styled inventory workbook</small></span></a>
                    </div>
                </div>
                <a class="btn" href="add_product.php">+ Add New Product</a>
            </div>
        </div>

        <form class="card inventory-filters" method="get" action="admin_panel.php">
            <div class="filter-grid">
                <label>Product ID<input type="number" min="1" name="id" value="<?php echo htmlspecialchars($filterId, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Any ID"></label>
                <label>Category<select name="category"><option value="">All categories</option><?php foreach ($categories as $category): ?><option value="<?php echo htmlspecialchars($category, ENT_QUOTES, 'UTF-8'); ?>" <?php echo strcasecmp($filterCategory, $category) === 0 ? 'selected' : ''; ?>><?php echo htmlspecialchars($category); ?></option><?php endforeach; ?></select></label>
                <label>Minimum price (PHP)<input type="number" min="0" step="0.01" name="min_price" value="<?php echo htmlspecialchars($minPrice, ENT_QUOTES, 'UTF-8'); ?>" placeholder="0.00"></label>
                <label>Maximum price (PHP)<input type="number" min="0" step="0.01" name="max_price" value="<?php echo htmlspecialchars($maxPrice, ENT_QUOTES, 'UTF-8'); ?>" placeholder="No limit"></label>
                <div class="filter-actions"><button class="btn" type="submit">Apply filters</button><a class="small-link edit" href="admin_panel.php">Clear</a></div>
            </div>
            <p class="filter-summary">Showing <?php echo count($products); ?> of <?php echo count($allProducts); ?> products. Export uses the currently applied filters.</p>
        </form>

        <div class="card" style="padding: 0;">
            <table>
                <tr>
                    <th style="padding-left: 20px;">ID</th>
                    <th>Name</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Category</th>
                    <th class="actions-column" style="padding-right: 20px;">Actions</th>
                </tr>
                <?php foreach ($products as $product): ?>
                    <tr>
                        <td style="padding-left: 20px;"><?php echo $product["id"]; ?></td>
                        <td><?php echo htmlspecialchars($product["name"]); ?></td>
                        <td>PHP <?php echo number_format($product["price"], 2); ?></td>
                        <td><?php echo $product["stock_quantity"]; ?></td>
                        <td><?php echo htmlspecialchars($product["category"]); ?></td>
                        <td class="actions-column" style="padding-right: 20px;">
                            <!-- Inventory link: size quantities are managed separately from product details. -->
                            <a class="small-link edit" href="manage_size_stock.php?id=<?php echo (int)$product["id"]; ?>">Sizes / Stock</a>
                            <a class="small-link edit" href="edit_product.php?id=<?php echo $product["id"]; ?>">Edit</a>
                            <a class="small-link delete" href="delete_product.php?id=<?php echo $product["id"]; ?>">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
    <script>
    (function(){
        const button=document.getElementById('exportButton'), menu=document.getElementById('exportMenu');
        if(!button||!menu)return;
        button.addEventListener('click',function(e){e.stopPropagation();const open=menu.classList.toggle('open');button.setAttribute('aria-expanded',String(open));});
        document.addEventListener('click',function(e){if(!menu.contains(e.target)&&!button.contains(e.target)){menu.classList.remove('open');button.setAttribute('aria-expanded','false');}});
        document.addEventListener('keydown',function(e){if(e.key==='Escape'){menu.classList.remove('open');button.setAttribute('aria-expanded','false');button.focus();}});
        document.getElementById('exportPdf').addEventListener('click',function(){menu.classList.remove('open');button.setAttribute('aria-expanded','false');if(confirm('Export the currently filtered clothing inventory as PDF? Choose “Save as PDF” in the print dialog.'))window.print();});
        menu.querySelector('a.export-option[href* ="export=excel"]').addEventListener('click',function(e){if(!confirm('Download the styled clothing inventory spreadsheet?'))e.preventDefault();});
    })();
    </script>
</body>
</html>
