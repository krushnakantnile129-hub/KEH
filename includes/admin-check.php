<?php
// Knowledge Exchange Hub - Admin Authorization Check
require_once __DIR__ . '/auth-check.php';

if (empty($current_user['is_admin']) || $current_user['is_admin'] != 1) {
    header("Location: ../dashboard.php?error=" . urlencode("Access denied. Admin privileges required."));
    exit();
}
?>
