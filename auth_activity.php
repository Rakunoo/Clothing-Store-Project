<?php
if (isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/UserDAO.php';
    markUserActive($_SESSION['user_id']);
}
?>
