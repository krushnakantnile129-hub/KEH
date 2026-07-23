<?php
// Knowledge Exchange Hub - User Authentication Check
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: login.php?error=" . urlencode("Please log in to access this page."));
    exit();
}

// Fetch logged in user details & check if blocked
require_once __DIR__ . '/db.php';
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$current_user = $stmt->fetch();

if (!$current_user || $current_user['is_blocked']) {
    session_unset();
    session_destroy();
    header("Location: login.php?error=" . urlencode("Your account has been suspended or does not exist."));
    exit();
}
?>
