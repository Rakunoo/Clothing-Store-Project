<?php
// Page purpose: Permanently delete a cancelled order owned by the signed-in customer.
session_start();
require_once __DIR__ . '/auth_activity.php';

// Access control: require a signed-in user and accept deletions only by POST.
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: my_orders.php');
    exit();
}

// CSRF protection: only accept the form token generated for this login session.
$sessionToken = $_SESSION['csrf_token'] ?? '';
$postedToken = $_POST['csrf_token'] ?? '';
if ($sessionToken === '' || !is_string($postedToken) || !hash_equals($sessionToken, $postedToken)) {
    $_SESSION['orders_message'] = 'The request could not be verified. Please try again.';
    $_SESSION['orders_message_type'] = 'error';
    header('Location: my_orders.php');
    exit();
}

// Data action: DAO confirms ownership and Cancelled status, then deletes order lines and order atomically.
require_once __DIR__ . '/OrderDAO.php';
$orderId = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
try {
    $deleted = $orderId ? deleteCancelledOrder($orderId, (int)$_SESSION['user_id']) : false;
} catch (Throwable $error) {
    $deleted = false;
}
$_SESSION['orders_message'] = $deleted
    ? 'Cancelled order deleted.'
    : 'That cancelled order could not be found or deleted.';
$_SESSION['orders_message_type'] = $deleted ? 'success' : 'error';

header('Location: my_orders.php');
exit();
?>
