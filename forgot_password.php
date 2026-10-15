<?php
session_start();
require_once __DIR__ . '/UserDAO.php';

$message = '';
$step = 1;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_otp'])) {
    $user = getUserByEmailAndPhone(trim($_POST['email'] ?? ''), trim($_POST['phone_number'] ?? ''));
    if ($user) {
        $otp = generateOtp($user['id']);
        $_SESSION['reset_user_id'] = $user['id'];
        $_SESSION['simulated_otp_display'] = $otp;
        $step = 2;
    } else {
        $message = 'No account found matching that email and phone number.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $newPassword = $_POST['new_password'] ?? '';
    $verifyPassword = $_POST['verify_password'] ?? '';
    $userId = $_SESSION['reset_user_id'] ?? null;
    $step = 2;
    if (!isPasswordStrong($newPassword)) {
        $message = 'Password must be at least 6 characters and include an uppercase letter, a number, and a symbol.';
    } elseif ($newPassword !== $verifyPassword) {
        $message = 'New password and verification do not match.';
    } elseif ($userId && verifyOtp($userId, $_POST['otp'] ?? '')) {
        resetPassword($userId, $newPassword);
        unset($_SESSION['reset_user_id'], $_SESSION['simulated_otp_display']);
        header('Location: login.php?reset=success');
        exit();
    } else {
        $message = 'Invalid or expired OTP. Please try again.';
    }
}
if (isset($_SESSION['reset_user_id'], $_SESSION['simulated_otp_display']) && $_SERVER['REQUEST_METHOD'] !== 'POST') $step = 2;
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Forgot Password - Clothify</title><link rel="stylesheet" href="style.css"><link rel="stylesheet" href="image-settings.php"></head>
<body>
    <main class="forgot-page"><section class="forgot-card">
        <p><a class="back" href="login.php">&larr; Back to Login</a></p>
        <h1>Forgot Password</h1>
        <?php if ($message): ?><p class="message error"><?php echo htmlspecialchars($message); ?></p><?php endif; ?>
        <?php if ($step === 1): ?>
            <p style="color:var(--muted);font-size:14px">Enter your email and phone number to receive a reset code.</p>
            <form method="POST" action="forgot_password.php">
                <label for="email">Email</label><input type="email" id="email" name="email" required>
                <label for="phone_number">Phone Number</label><input type="tel" id="phone_number" name="phone_number" required>
                <button type="submit" name="request_otp" class="btn btn-block" style="margin-top:18px">Send OTP</button>
            </form>
        <?php else: ?>
            <div class="otp-box"><strong>Simulated SMS:</strong> Reset code for this demo:<br>Your OTP: <span class="otp-code"><?php echo htmlspecialchars($_SESSION['simulated_otp_display'] ?? ''); ?></span><br><small style="color:var(--muted)">Expires in 10 minutes.</small></div>
            <form method="POST" action="forgot_password.php" data-password-form>
                <label for="otp">Enter OTP</label><input type="text" id="otp" name="otp" required>
                <label for="new_password">New Password</label>
                <div class="password-wrapper"><input type="password" name="new_password" id="new_password" data-new-password autocomplete="new-password" minlength="6" required><button type="button" class="toggle-eye" aria-label="Show password" aria-pressed="false" data-password-toggle="new_password">&#128065;</button></div>
                <p class="password-hint">At least 6 characters, with 1 uppercase letter, 1 number, and 1 symbol.</p>
                <label for="verify_password">Verify Password</label>
                <div class="password-wrapper"><input type="password" name="verify_password" id="verify_password" autocomplete="new-password" required><button type="button" class="toggle-eye" aria-label="Show password" aria-pressed="false" data-password-toggle="verify_password">&#128065;</button></div>
                <button type="submit" name="reset_password" class="btn btn-block" style="margin-top:18px">Reset Password</button>
            </form>
        <?php endif; ?>
    </section></main>
    <script src="password-fields.js"></script>
</body>
</html>

