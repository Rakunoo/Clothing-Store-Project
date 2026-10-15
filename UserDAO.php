<?php
require_once __DIR__ . '/db.php';

function migrateLegacyPasswords() {
    static $done = false;
    if ($done) return;
    $done = true;

    $conn = getConnection();
    $passwordColumn = $conn->query("SHOW COLUMNS FROM users LIKE 'password'")->fetch(PDO::FETCH_ASSOC);
    if ($passwordColumn && preg_match('/^varchar\((\d+)\)$/i', $passwordColumn['Type'], $match) && (int)$match[1] < 255) {
        $conn->exec("ALTER TABLE users MODIFY password VARCHAR(255) NOT NULL");
    }
    $rows = $conn->query("SELECT id, password FROM users")->fetchAll(PDO::FETCH_ASSOC);
    $update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    foreach ($rows as $row) {
        $info = password_get_info($row['password']);
        if (empty($info['algo']) || $info['algo'] === 'unknown') {
            $update->execute([password_hash($row['password'], PASSWORD_DEFAULT), $row['id']]);
        }
    }
}

function isPasswordStrong($password) {
    return strlen($password) >= 6
        && preg_match('/[A-Z]/', $password)
        && preg_match('/[0-9]/', $password)
        && preg_match('/[^A-Za-z0-9]/', $password);
}

function getAllUsers() {
    $conn = getConnection();
    return $conn->query("SELECT * FROM users")->fetchAll(PDO::FETCH_ASSOC);
}

function getUserStatistics() {
    $conn = getConnection();
    $counts = $conn->query("SELECT COUNT(*) AS registered, SUM(role = 'customer') AS customers, SUM(role = 'admin') AS administrators FROM users")->fetch(PDO::FETCH_ASSOC);
    $active = (int)$conn->query("SELECT COUNT(*) FROM user_activity WHERE last_seen >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)")->fetchColumn();
    return [
        'registered' => (int)$counts['registered'],
        'customers' => (int)$counts['customers'],
        'administrators' => (int)$counts['administrators'],
        'active' => $active
    ];
}

function markUserActive($userId) {
    static $tableReady = false;
    $conn = getConnection();
    if (!$tableReady) {
        $conn->exec("CREATE TABLE IF NOT EXISTS user_activity (user_id INT NOT NULL PRIMARY KEY, last_seen DATETIME NOT NULL)");
        $tableReady = true;
    }
    $stmt = $conn->prepare("INSERT INTO user_activity (user_id, last_seen) VALUES (?, NOW()) ON DUPLICATE KEY UPDATE last_seen = NOW()");
    $stmt->execute([$userId]);
}

function insertUser($name, $email, $password, $address, $role, $phoneNumber) {
    $conn = getConnection();
    $sql = "INSERT INTO users (name, email, password, address, role, phone_number) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    return $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $address, $role, $phoneNumber]);
}

function getUserByEmail($email) {
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    return $user ?: null;
}

function getUserById($id) {
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    return $user ?: null;
}

function getUserByEmailAndPhone($email, $phoneNumber) {
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND phone_number = ?");
    $stmt->execute([$email, $phoneNumber]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    return $user ?: null;
}

function generateOtp($userId) {
    $conn = getConnection();
    $otp = strval(random_int(100000, 999999));
    $stmt = $conn->prepare("UPDATE users SET otp = ?, otp_expiry = DATE_ADD(NOW(), INTERVAL 10 MINUTE) WHERE id = ?");
    $stmt->execute([$otp, $userId]);
    return $otp;
}

function verifyOtp($userId, $enteredOtp) {
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT id FROM users WHERE id = ? AND otp = ? AND otp_expiry > NOW()");
    $stmt->execute([$userId, $enteredOtp]);
    return (bool)$stmt->fetchColumn();
}

function resetPassword($userId, $newPassword) {
    $conn = getConnection();
    $stmt = $conn->prepare("UPDATE users SET password = ?, otp = NULL, otp_expiry = NULL WHERE id = ?");
    return $stmt->execute([password_hash($newPassword, PASSWORD_DEFAULT), $userId]);
}

function updateUserProfile($userId, $name, $address) {
    $conn = getConnection();
    $stmt = $conn->prepare("UPDATE users SET name = ?, address = ? WHERE id = ?");
    return $stmt->execute([$name, $address, $userId]);
}

function updateUserPassword($userId, $newPassword) {
    $conn = getConnection();
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    return $stmt->execute([password_hash($newPassword, PASSWORD_DEFAULT), $userId]);
}

migrateLegacyPasswords();
?>
