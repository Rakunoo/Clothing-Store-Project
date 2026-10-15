<?php
session_start();
require_once __DIR__ . '/auth_activity.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
require_once __DIR__ . '/UserDAO.php';

$userId = $_SESSION['user_id'];
$user = getUserById($userId);
$profileMessage = '';
$passwordMessage = '';
$passwordSuccess = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $newName = trim($_POST['name'] ?? '');
    $newAddress = trim($_POST['address'] ?? '');
    if ($newName === '' || $newAddress === '') {
        $profileMessage = 'Name and address cannot be empty.';
    } else {
        updateUserProfile($userId, $newName, $newAddress);
        $_SESSION['user_name'] = $newName;
        $profileMessage = 'Profile updated successfully!';
        $user = getUserById($userId);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $verifyPassword = $_POST['verify_password'] ?? '';
    if (!password_verify($currentPassword, $user['password'])) {
        $passwordMessage = 'Current password is incorrect.';
    } elseif (!isPasswordStrong($newPassword)) {
        $passwordMessage = 'New password must be at least 6 characters and include an uppercase letter, a number, and a symbol.';
    } elseif ($newPassword !== $verifyPassword) {
        $passwordMessage = 'New password and verification do not match.';
    } else {
        updateUserPassword($userId, $newPassword);
        $passwordMessage = 'Password changed successfully!';
        $passwordSuccess = true;
        $user = getUserById($userId);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Settings - Clothify</title><link rel="stylesheet" href="style.css"></head>
<body class="app-body">
    <?php $isAdminPage = false; require __DIR__ . '/includes/navbar.php'; ?>
    <main class="page narrow">
        <h1 style="margin-bottom:24px">Settings</h1>
        <div class="card">
            <h2>Profile</h2>
            <?php if ($profileMessage): ?><p class="message <?php echo strpos($profileMessage, 'successfully') !== false ? 'success' : 'error'; ?>"><?php echo htmlspecialchars($profileMessage); ?></p><?php endif; ?>
            <form method="POST" action="settings.php">
                <label for="name">Display Name</label><input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                <label for="address">Address</label><input type="text" id="address" name="address" value="<?php echo htmlspecialchars($user['address']); ?>" required>
                <button type="submit" name="update_profile" class="btn" style="margin-top:16px">Save Profile</button>
            </form>
        </div>
        <div class="card">
            <h2>Change Password</h2>
            <?php if ($passwordMessage): ?><p class="message <?php echo $passwordSuccess ? 'success' : 'error'; ?>"><?php echo htmlspecialchars($passwordMessage); ?></p><?php endif; ?>
            <form method="POST" action="settings.php" data-password-form>
                <label for="current_password">Current Password</label>
                <div class="password-wrapper"><input type="password" name="current_password" id="current_password" autocomplete="current-password" required><button type="button" class="toggle-eye" aria-label="Show password" aria-pressed="false" data-password-toggle="current_password">&#128065;</button></div>
                <label for="new_password">New Password</label>
                <div class="password-wrapper"><input type="password" name="new_password" id="new_password" data-new-password autocomplete="new-password" minlength="6" required><button type="button" class="toggle-eye" aria-label="Show password" aria-pressed="false" data-password-toggle="new_password">&#128065;</button></div>
                <p class="password-hint">At least 6 characters, with 1 uppercase letter, 1 number, and 1 symbol.</p>
                <label for="verify_password">Verify Password</label>
                <div class="password-wrapper"><input type="password" name="verify_password" id="verify_password" autocomplete="new-password" required><button type="button" class="toggle-eye" aria-label="Show password" aria-pressed="false" data-password-toggle="verify_password">&#128065;</button></div>
                <button type="submit" name="update_password" class="btn" style="margin-top:18px">Change Password</button>
            </form>
        </div>
    </main>
    <script src="password-fields.js"></script>
</body>
</html>
