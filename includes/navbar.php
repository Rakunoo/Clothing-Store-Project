<?php
$navBase = isset($isAdminPage) && $isAdminPage ? '../' : '';
$isAdminUser = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
?>
<?php
$currentPage = basename($_SERVER['SCRIPT_NAME'] ?? '');
$navLink = function ($href, $label, $pageNames) use ($currentPage) {
    $current = in_array($currentPage, (array)$pageNames, true);
    return '<a class="nav-link' . ($current ? ' is-current' : '') . '" href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '"' . ($current ? ' aria-current="page"' : '') . '>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</a>';
};
?>
<aside class="navbar" aria-label="Main navigation">
    <a class="brand" href="<?php echo $navBase; ?>dashboard.php">Clothify<span>STYLE THAT FEELS LIKE YOU</span></a>
    <nav class="nav-links">
        <?php echo $navLink($navBase . 'dashboard.php', 'Dashboard', 'dashboard.php'); ?>
        <?php echo $navLink($navBase . 'shop.php', 'Shop', 'shop.php'); ?>
        <?php echo $navLink($navBase . 'cart.php', 'Cart', 'cart.php'); ?>
        <?php echo $navLink($navBase . 'my_orders.php', 'My Orders', 'my_orders.php'); ?>
        <?php echo $navLink($navBase . 'settings.php', 'Settings', 'settings.php'); ?>
        <?php if ($isAdminUser): ?>
            <div class="nav-group">
                <?php echo $navLink($navBase . 'admin/admin_panel.php', 'Admin Panel', 'admin_panel.php'); ?>
                <div class="nav-sub-links" aria-label="Admin Panel pages">
                    <?php echo $navLink($navBase . 'admin/admin_orders.php', 'Orders', 'admin_orders.php'); ?>
                    <?php echo $navLink($navBase . 'admin/admin_reports.php', 'Reports', 'admin_reports.php'); ?>
                </div>
            </div>
        <?php endif; ?>
    </nav>
    <a class="nav-link nav-logout" href="<?php echo $navBase; ?>logout.php"><svg class="logout-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M10 17l5-5-5-5M15 12H3m9-7h7a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2h-7" /></svg><span>Logout</span></a>
</aside>
