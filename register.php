<?php
require_once __DIR__ . '/UserDAO.php';

$message = '';
$successMessage = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $verifyPassword = $_POST['verify_password'] ?? '';
    $address = trim($_POST['address'] ?? '');
    $phoneNumber = trim($_POST['phone_number'] ?? '');

    if (!isPasswordStrong($password)) {
        $message = 'Password must be at least 6 characters and include an uppercase letter, a number, and a symbol.';
    } elseif ($password !== $verifyPassword) {
        $message = 'Passwords do not match.';
    } else {
        try {
            $successMessage = insertUser($name, $email, $password, $address, 'customer', $phoneNumber);
            $message = $successMessage ? 'Registration successful! You can now log in.' : 'Registration failed. Please try again.';
        } catch (PDOException $e) {
            $message = 'That email may already be registered. Please check it and try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register - Clothify</title><link rel="stylesheet" href="style.css"><link rel="stylesheet" href="image-settings.php">
</head>
<body class="register-page">
    <main class="auth-page">
        <section class="auth-gallery"><div class="auth-gallery-content"><h2>Make room for something new.</h2><p>Build a wardrobe that feels like you, with easy pieces made for everyday life.</p></div></section>
        <section class="auth-form-panel"><div class="auth-form-content">
            <a class="auth-brand" href="login.php">Clothify</a>
            <h1>Create your account</h1><p style="color:var(--muted);margin:0 0 20px">Join us and discover your style.</p>
            <div class="card">
                <?php if ($message): ?><p class="message <?php echo $successMessage ? 'success' : 'error'; ?>"><?php echo htmlspecialchars($message); ?></p><?php endif; ?>
                <form method="POST" action="register.php" data-password-form>
                    <label for="name">Full Name</label><input type="text" id="name" name="name" autocomplete="name" required>
                    <label for="email">Email</label><input type="email" id="email" name="email" autocomplete="email" required>
                    <label for="password">New Password</label>
                    <div class="password-wrapper"><input type="password" name="password" id="password" data-new-password autocomplete="new-password" minlength="6" required><button type="button" class="toggle-eye" aria-label="Show password" aria-pressed="false" data-password-toggle="password">&#128065;</button></div>
                    <p class="password-hint">At least 6 characters, with 1 uppercase letter, 1 number, and 1 symbol.</p>
                    <label for="verify_password">Verify Password</label>
                    <div class="password-wrapper"><input type="password" name="verify_password" id="verify_password" autocomplete="new-password" required><button type="button" class="toggle-eye" aria-label="Show password" aria-pressed="false" data-password-toggle="verify_password">&#128065;</button></div>
                    <label for="address">Address</label><input type="text" id="address" name="address" autocomplete="street-address" required>
                    <label for="phone_number">Phone Number</label><input type="tel" id="phone_number" name="phone_number" placeholder="09171234567" autocomplete="tel" required>
                    <button type="submit" class="btn btn-block" style="margin-top:20px">Register</button>
                </form>
            </div>
            <p style="text-align:center;font-size:14px;color:var(--muted)">Already have an account? <a href="login.php">Login here</a></p>
        </div></section>
    </main>
    <script src="password-fields.js"></script>
</body>
</html>


