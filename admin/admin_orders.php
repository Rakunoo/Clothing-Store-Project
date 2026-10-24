<?php
// Page purpose: Admin order management: update statuses and delete cancelled orders individually or in bulk.
session_start();
require_once __DIR__ . '/../auth_activity.php';

// Access control: this page is available only to signed-in administrators.
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}
if (($_SESSION['user_role'] ?? '') !== 'admin') {
    echo "<p style='font-family: Arial; margin: 60px;'>Access denied. Admins only. <a href='../dashboard.php'>Back to Dashboard</a></p>";
    exit();
}

// Data access: load order and stock operations used by the admin page.
require_once __DIR__ . '/../OrderDAO.php';
require_once __DIR__ . '/../OrderItemDAO.php';
require_once __DIR__ . '/../SizeStockDAO.php';

$statusOptions = ['Pending', 'Processing', 'Delivered', 'Completed', 'Cancelled'];
$csrfToken = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrfToken;

// Request handling: verify the session token, then perform the selected admin action.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedToken = $_POST['csrf_token'] ?? '';
    if (!is_string($postedToken) || !hash_equals($csrfToken, $postedToken)) {
        $_SESSION['admin_orders_message'] = 'The request could not be verified. Please try again.';
        $_SESSION['admin_orders_message_type'] = 'error';
    } else {
        $action = $_POST['action'] ?? 'update_status';
        if ($action === 'delete_cancelled_all') {
            try {
                $deletedCount = deleteAllCancelledOrders();
                $_SESSION['admin_orders_message'] = $deletedCount > 0
                    ? "Deleted $deletedCount cancelled order(s)."
                    : 'There are no cancelled orders to delete.';
                $_SESSION['admin_orders_message_type'] = $deletedCount > 0 ? 'success' : 'error';
            } catch (Throwable $error) {
                $_SESSION['admin_orders_message'] = 'Cancelled orders could not be deleted. Please try again.';
                $_SESSION['admin_orders_message_type'] = 'error';
            }
        } elseif ($action === 'delete_cancelled_one') {
            $orderId = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
            try {
                $deleted = $orderId ? deleteCancelledOrderById($orderId) : false;
                $_SESSION['admin_orders_message'] = $deleted
                    ? "Cancelled order #$orderId deleted."
                    : 'That order was not found or is no longer cancelled.';
                $_SESSION['admin_orders_message_type'] = $deleted ? 'success' : 'error';
            } catch (Throwable $error) {
                $_SESSION['admin_orders_message'] = 'The cancelled order could not be deleted. Please try again.';
                $_SESSION['admin_orders_message_type'] = 'error';
            }
        } elseif ($action === 'save_all_statuses') {
            $postedStatuses = $_POST['statuses'] ?? null;
            $valid = is_array($postedStatuses);
            if ($valid) {
                foreach ($postedStatuses as $orderId => $newStatus) {
                    if (!ctype_digit((string)$orderId) || !in_array($newStatus, $statusOptions, true) || !getOrderById((int)$orderId)) {
                        $valid = false;
                        break;
                    }
                }
            }
            if (!$valid) {
                $_SESSION['admin_orders_message'] = 'One or more order statuses were invalid. No changes were saved.';
                $_SESSION['admin_orders_message_type'] = 'error';
            } else {
                $updatedCount = 0;
                foreach ($postedStatuses as $orderId => $newStatus) {
                    $orderId = (int)$orderId;
                    $currentOrder = getOrderById($orderId);
                    if (!$currentOrder || $currentOrder['status'] === $newStatus) continue;
                    if ($newStatus === 'Cancelled' && $currentOrder['status'] !== 'Cancelled') {
                        // Stock restoration: return each line to the size that was ordered when cancelling.
                        foreach (getOrderItemsByOrderId($orderId) as $item) {
                            restoreSizeStock($item['product_id'], strtoupper($item['size'] ?? 'M'), (int)$item['quantity']);
                        }
                    }
                    if (updateOrderStatus($orderId, $newStatus)) $updatedCount++;
                }
                $_SESSION['admin_orders_message'] = "Saved status changes for $updatedCount order(s).";
                $_SESSION['admin_orders_message_type'] = 'success';
            }
        } else {
            $orderId = filter_input(INPUT_POST, 'update_order_id', FILTER_VALIDATE_INT);
            $newStatus = $_POST['statuses'][$orderId] ?? '';
            if ($orderId && in_array($newStatus, $statusOptions, true)) {
                $currentOrder = getOrderById($orderId);
                if ($newStatus === 'Cancelled' && $currentOrder && $currentOrder['status'] !== 'Cancelled') {
                    // Stock restoration: return each line to the size that was ordered when cancelling.
                    foreach (getOrderItemsByOrderId($orderId) as $item) {
                        restoreSizeStock($item['product_id'], strtoupper($item['size'] ?? 'M'), (int)$item['quantity']);
                    }
                }
                if ($currentOrder && updateOrderStatus($orderId, $newStatus)) {
                    $_SESSION['admin_orders_message'] = "Order #$orderId updated.";
                    $_SESSION['admin_orders_message_type'] = 'success';
                } else {
                    $_SESSION['admin_orders_message'] = 'The order status could not be updated.';
                    $_SESSION['admin_orders_message_type'] = 'error';
                }
            } else {
                $_SESSION['admin_orders_message'] = 'Choose a valid order status and try again.';
                $_SESSION['admin_orders_message_type'] = 'error';
            }
        }
    }
    header('Location: admin_orders.php');
    exit();
}

// Page data: load orders and count the cancelled ones for the bulk-delete control.
$allOrders = getAllOrdersWithUsers();
$statusFilter = trim((string)($_GET['status'] ?? ''));
if ($statusFilter !== '' && !in_array($statusFilter, $statusOptions, true)) $statusFilter = '';
$orders = array_values(array_filter($allOrders, static fn($order) => $statusFilter === '' || ($order['status'] ?? '') === $statusFilter));

if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    $filename = 'clothify_orders_' . ($statusFilter !== '' ? strtolower($statusFilter) . '_' : 'all_statuses_') . date('Y-m-d') . '.xls';
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-store, no-cache, must-revalidate');

    $escape = static function ($value) {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    };
    $statusCounts = array_fill_keys($statusOptions, 0);
    foreach ($orders as $order) {
        $status = (string)($order['status'] ?? 'Unknown');
        if (!isset($statusCounts[$status])) $statusCounts[$status] = 0;
        $statusCounts[$status]++;
    }
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>
        body{font-family:Calibri,Arial,sans-serif;color:#1f2937}
        table{border-collapse:collapse;width:100%}
        td,th{border:1px solid #d7dee8;padding:9px 12px}
        .title{background:#142b4a;color:#fff;font-size:20px;font-weight:bold;padding:15px}
        .meta{background:#eef3f8;color:#46566b}
        .section{background:#24558a;color:#fff;font-weight:bold;font-size:14px}
        th{background:#1d3557;color:#fff;text-align:left;font-weight:bold}
        .num{text-align:right;mso-number-format:"\\#\\,\\#\\#0.00"}
        .center{text-align:center}
        .alt{background:#f3f6fa}
        .total{background:#dce8f5;font-weight:bold}
    </style></head><body><table>';
    echo '<tr><td class="title" colspan="6">CLOTHIFY | ORDERS REPORT' . ($statusFilter !== '' ? ' — ' . $escape(strtoupper($statusFilter)) : ' — ALL STATUSES') . '</td></tr>';
    echo '<tr><td class="meta" colspan="6">Generated: ' . $escape(date('d M Y, h:i A')) . ' | Orders included: ' . count($orders) . '</td></tr>';
    echo '<tr><td class="section" colspan="3">ORDER STATUS SUMMARY</td><td class="section" colspan="3">REPORT TOTALS</td></tr>';
    echo '<tr><th>Status</th><th>Order Count</th><th>Share</th><th>Metric</th><th colspan="2">Value</th></tr>';
    $totalOrders = count($orders);
    $summaryRows = max(count($statusCounts), 2);
    $statuses = array_keys($statusCounts);
    for ($i = 0; $i < $summaryRows; $i++) {
        $status = $statuses[$i] ?? '';
        $count = $status !== '' ? $statusCounts[$status] : '';
        $share = ($status !== '' && $totalOrders > 0) ? number_format(($count / $totalOrders) * 100, 1) . '%' : '';
        $metric = $i === 0 ? 'Total orders' : ($i === 1 ? 'Total order value (PHP)' : '');
        $metricValue = $i === 0 ? $totalOrders : ($i === 1 ? number_format(array_sum(array_map(static function($o){return (float)($o['total_amount'] ?? 0);}, $orders)), 2, '.', ',') : '');
        echo '<tr' . ($i % 2 ? ' class="alt"' : '') . '><td>' . $escape($status) . '</td><td class="center">' . $escape($count) . '</td><td class="center">' . $escape($share) . '</td><td>' . $escape($metric) . '</td><td colspan="2" class="num">' . $escape($metricValue) . '</td></tr>';
    }
    echo '<tr><td class="section" colspan="6">ORDER DETAILS</td></tr>';
    echo '<tr><th>Order #</th><th>Buyer</th><th>Order Date</th><th>Total (PHP)</th><th>Status</th><th>Items &amp; Sizes</th></tr>';
    foreach ($orders as $i => $order) {
        $itemsText = [];
        foreach (getItemsWithProductInfo($order['id']) as $item) {
            $itemsText[] = ($item['product_name'] ?? 'Product') . ' - ' . ($item['size'] ?? 'M') . ' x ' . (int)($item['quantity'] ?? 0);
        }
        echo '<tr' . ($i % 2 ? ' class="alt"' : '') . '><td>#' . $escape($order['id']) . '</td><td>' . $escape($order['buyer_name']) . '</td><td>' . $escape(date('d M Y, h:i A', strtotime((string)$order['order_date']) ?: time())) . '</td><td class="num">' . number_format((float)$order['total_amount'], 2, '.', ',') . '</td><td>' . $escape($order['status']) . '</td><td>' . $escape(implode('; ', $itemsText)) . '</td></tr>';
    }
    echo '<tr class="total"><td colspan="3">TOTAL ORDER VALUE (PHP)</td><td class="num">' . number_format(array_sum(array_map(static function($o){return (float)($o['total_amount'] ?? 0);}, $orders)), 2, '.', ',') . '</td><td colspan="2">Total orders: ' . $totalOrders . '</td></tr>';
    echo '</table></body></html>';
    exit();
}

$cancelledOrderCount = count(array_filter($orders, function ($order) {
    return $order['status'] === 'Cancelled';
}));
$flashMessage = $_SESSION['admin_orders_message'] ?? '';
$flashType = $_SESSION['admin_orders_message_type'] ?? 'success';
unset($_SESSION['admin_orders_message'], $_SESSION['admin_orders_message_type']);
?>
<!-- Page display: render order details, status controls, and safe cancelled-order deletion actions. -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Orders - Clothify</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .order-status-filter{display:flex;align-items:end;gap:10px;flex-wrap:wrap;padding:16px 18px;margin:0 0 16px;border:1px solid #e3e8ef;border-radius:12px;background:#fff}.order-status-filter label{font-size:12px;font-weight:700;color:#46566b}.order-status-filter select{padding:9px 11px;border:1px solid #d7dee8;border-radius:8px;background:#fff}.filter-note{width:100%;font-size:12px;color:#788496}.status-form { display: flex; gap: 8px; align-items: center; }
        .status-form select { width: auto; padding: 6px 8px; }
        .status-form button { padding: 6px 12px; font-size: 13px; }
        .order-admin-actions { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; }
        .order-admin-actions form { margin: 0; }
        .order-admin-actions .btn-danger { padding: 6px 10px; font-size: 12px; white-space: nowrap; }
        .bulk-delete-bar { display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; margin: 0 0 14px; }
        .bulk-delete-bar form { margin: 0; }
        .bulk-delete-bar button { padding: 9px 14px; }
        .orders-heading{display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;margin-bottom:24px}
        .orders-heading h1{margin:0}
        .orders-heading p{margin:7px 0 0;color:#778293;font-size:14px}
        .export-wrap{position:relative}
        .export-button{display:inline-flex;align-items:center;gap:9px;padding:10px 15px;border:1px solid #d7dce2;border-radius:9px;background:#fff;color:#202938;font:inherit;font-weight:600;cursor:pointer}
        .export-button:hover{background:#f5f7fa}
        .export-menu{display:none;position:absolute;right:0;top:calc(100% + 8px);z-index:50;width:225px;padding:7px;background:#fff;border:1px solid #e2e7ee;border-radius:12px;box-shadow:0 12px 30px #172b4d20}
        .export-menu.open{display:block}
        .export-menu-title{padding:8px 10px;color:#788496;font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase}
        .export-option{display:flex;align-items:center;gap:10px;width:100%;box-sizing:border-box;padding:11px 10px;border:0;border-radius:8px;background:transparent;color:#202938;text-decoration:none;text-align:left;font:inherit;cursor:pointer}
        .export-option:hover{background:#f1f5f9}
        .export-option strong{display:block;font-size:14px}.export-option small{display:block;margin-top:3px;color:#788496;font-size:12px}
        @media(max-width:600px){.orders-heading .export-wrap{width:100%}.orders-heading .export-button{width:100%;justify-content:center}.orders-heading .export-menu{width:100%;box-sizing:border-box}}
        @media(max-width:600px){.order-status-filter select{flex:1}}
        @media print{.order-status-filter,.export-wrap,.bulk-delete-bar,.order-admin-actions,nav,.navbar,header{display:none!important}.page{max-width:none!important}.card{box-shadow:none!important;border:1px solid #ddd!important}}
    </style>
</head>
<body class="app-body">
    <?php $isAdminPage = true; require __DIR__ . '/../includes/navbar.php'; ?>

    <main class="page">
        <div class="orders-heading">
            <div>
                <h1>Manage Orders</h1>
                <p>Track order status, review purchases, and export a summary for reporting.</p>
            </div>
            <div class="export-wrap">
                <button class="export-button" id="exportButton" type="button" aria-haspopup="true" aria-expanded="false">↓ Export orders ▾</button>
                <div class="export-menu" id="exportMenu" role="menu">
                    <div class="export-menu-title">Choose file format</div>
                    <button class="export-option" type="button" id="exportPdf" role="menuitem"><span>📄</span><span><strong>PDF document</strong><small>Print or save as PDF</small></span></button>
                    <a class="export-option" id="exportExcel" href="?export=excel&amp;status=<?php echo rawurlencode($statusFilter); ?>" role="menuitem"><span>📊</span><span><strong>Excel spreadsheet</strong><small>Status summary and order details</small></span></a>
                </div>
            </div>
        </div>
        <form class="order-status-filter" method="get" action="admin_orders.php">
            <label for="statusFilter">Export / view by status</label>
            <select id="statusFilter" name="status">
                <option value="">All statuses</option>
                <?php foreach ($statusOptions as $option): ?><option value="<?php echo htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $statusFilter === $option ? 'selected' : ''; ?>><?php echo htmlspecialchars($option); ?></option><?php endforeach; ?>
            </select>
            <button class="btn" type="submit">Apply status</button>
            <a class="small-link edit" href="admin_orders.php">Clear</a>
            <span class="filter-note">Showing <?php echo count($orders); ?> of <?php echo count($allOrders); ?> orders. Exports use this status.</span>
        </form>

        <?php if ($flashMessage !== ''): ?>
            <p class="message <?php echo $flashType === 'error' ? 'error' : 'success'; ?>"><?php echo htmlspecialchars($flashMessage); ?></p>
        <?php endif; ?>

        <?php if (count($orders) > 0): ?>
            <div class="bulk-delete-bar">
            <form id="orders-status-form" method="POST" action="admin_orders.php">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
            </form>
            <button type="submit" form="orders-status-form" name="action" value="save_all_statuses" class="btn">Save All</button>
            <?php if ($cancelledOrderCount > 0): ?>
            <form method="POST" action="admin_orders.php" onsubmit="return confirm('Permanently delete all <?php echo $cancelledOrderCount; ?> cancelled orders and their items?');">
                <input type="hidden" name="action" value="delete_cancelled_all">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                <button type="submit" class="btn btn-danger">Delete all cancelled orders (<?php echo $cancelledOrderCount; ?>)</button>
            </form>
            <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (count($orders) === 0): ?>
            <p class="empty-state">No orders have been placed yet.</p>
        <?php else: ?>
            <div class="card" style="padding: 0;">
                <table>
                    <tr>
                        <th style="padding-left: 20px;">Order #</th>
                        <th>Buyer</th>
                        <th>Items &amp; Sizes</th>
                        <th>Date</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th style="padding-right: 20px;">Actions</th>
                    </tr>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td style="padding-left: 20px;">#<?php echo (int)$order['id']; ?></td>
                            <td><?php echo htmlspecialchars($order['buyer_name']); ?></td>
                            <td><div class="order-items-list"><?php foreach (getItemsWithProductInfo($order['id']) as $item): ?><div><?php echo htmlspecialchars($item['product_name']); ?> &middot; <?php echo htmlspecialchars($item['size'] ?? 'M'); ?> &times; <?php echo (int)$item['quantity']; ?></div><?php endforeach; ?></div></td>
                            <td><?php echo htmlspecialchars($order['order_date']); ?></td>
                            <td>PHP <?php echo number_format((float)$order['total_amount'], 2); ?></td>
                            <td><span class="status-badge"><?php echo htmlspecialchars($order['status']); ?></span></td>
                            <td style="padding-right: 20px;">
                                <div class="order-admin-actions">
                                    <div class="status-form">
                                        <select form="orders-status-form" name="statuses[<?php echo (int)$order['id']; ?>]">
                                            <?php foreach ($statusOptions as $option): ?>
                                                <option value="<?php echo htmlspecialchars($option); ?>" <?php if ($order['status'] === $option) echo 'selected'; ?>><?php echo htmlspecialchars($option); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" form="orders-status-form" name="update_order_id" value="<?php echo (int)$order['id']; ?>" class="btn">Save</button>
                                    </div>
                                    <?php if ($order['status'] === 'Cancelled'): ?>
                                        <form method="POST" action="admin_orders.php" onsubmit="return confirm('Permanently delete cancelled order #<?php echo (int)$order['id']; ?> and its items?');">
                                            <input type="hidden" name="action" value="delete_cancelled_one">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="order_id" value="<?php echo (int)$order['id']; ?>">
                                            <button type="submit" class="btn btn-danger">Delete</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        <?php endif; ?>
    </main>
    <script>
    (function(){
        const button=document.getElementById('exportButton'), menu=document.getElementById('exportMenu');
        if(!button||!menu)return;
        button.addEventListener('click',function(e){e.stopPropagation();const open=menu.classList.toggle('open');button.setAttribute('aria-expanded',String(open));});
        document.addEventListener('click',function(e){if(!menu.contains(e.target)&&!button.contains(e.target)){menu.classList.remove('open');button.setAttribute('aria-expanded','false');}});
        document.addEventListener('keydown',function(e){if(e.key==='Escape'){menu.classList.remove('open');button.setAttribute('aria-expanded','false');button.focus();}});
        document.getElementById('exportPdf').addEventListener('click',function(){menu.classList.remove('open');button.setAttribute('aria-expanded','false');if(confirm('Export the currently selected order status as PDF? Choose “Save as PDF” in the print dialog.'))window.print();});
        menu.querySelector('#exportExcel').addEventListener('click',function(e){if(!confirm('Download the styled orders summary and order details spreadsheet?'))e.preventDefault();});
    })();
    </script>
</body>
</html>
