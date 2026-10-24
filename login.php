<?php
session_start();
require_once __DIR__ . '/UserDAO.php';

$message = '';
$notice = isset($_GET['reset']) ? 'Your password was reset. Please sign in.' : '';
// Only allow a short list of local pages as the post-login destination.
$allowedNext = ['shop.php'];
$next = '';
if (isset($_GET['next']) && is_string($_GET['next'])) {
    $next = $_GET['next'];
} elseif (isset($_POST['next']) && is_string($_POST['next'])) {
    $next = $_POST['next'];
}
if (!in_array($next, $allowedNext, true)) {
    $next = '';
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = getUserByEmail(trim($_POST['email'] ?? ''));
    if ($user && password_verify($_POST['password'] ?? '', $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        markUserActive($user['id']);
        header('Location: ' . ($next !== '' ? $next : 'dashboard.php'));
        exit();
    }
    $message = 'Invalid email or password.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Clothify</title><link rel="stylesheet" href="style.css"><link rel="stylesheet" href="image-settings.php">
</head>
<body class="login-page">
    <main class="auth-page">
        <section class="auth-gallery"><div class="auth-gallery-content"><h2>Find your next favorite.</h2><p>Everyday pieces, thoughtful details, and a little inspiration for getting dressed.</p></div></section>
        <section class="auth-form-panel"><div class="auth-form-content">
            <a class="auth-brand" href="login.php">Clothify</a>
            <h1>Welcome back</h1><p style="color:var(--muted);margin:0 0 24px">Sign in to continue shopping.</p>
            <div class="card">
                <?php if ($notice): ?><p class="message success"><?php echo htmlspecialchars($notice); ?></p><?php endif; ?>
                <?php if ($message): ?><p class="message error"><?php echo htmlspecialchars($message); ?></p><?php endif; ?>
                <form method="POST" action="login.php">
                    <?php if ($next !== ''): ?><input type="hidden" name="next" value="<?php echo htmlspecialchars($next, ENT_QUOTES, 'UTF-8'); ?>"><?php endif; ?>
                    <label for="email">Email</label><input type="email" id="email" name="email" autocomplete="email" required>
                    <label for="password">Password</label>
                    <div class="password-wrapper"><input type="password" name="password" id="password" autocomplete="current-password" required><button type="button" class="toggle-eye" aria-label="Show password" aria-pressed="false" data-password-toggle="password">&#128065;</button></div>
                    <button type="submit" class="btn btn-block" style="margin-top:20px">Login</button>
                </form>
            </div>
            <p style="text-align:center;font-size:14px"><a href="forgot_password.php">Forgot Password?</a></p>
            <p style="text-align:center;font-size:14px;color:var(--muted)">Don't have an account? <a href="register.php">Register here</a></p>
        </div></section>
    </main>
    <script src="password-fields.js"></script>
</body>
</html>


