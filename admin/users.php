<?php
$page_title = "Admin - Manage Users";
require_once __DIR__ . '/../includes/admin-check.php';
require_once __DIR__ . '/../includes/functions.php';

$msg = $_GET['msg'] ?? '';
$error = $_GET['error'] ?? '';

// Handle Block/Unblock & Delete Actions
if (isset($_GET['action'])) {
    $target_user_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    $action         = $_GET['action'];

    if ($target_user_id && $target_user_id !== (int)$current_user['id']) {
        if ($action === 'block') {
            $stmt = $pdo->prepare("UPDATE users SET is_blocked = 1 WHERE id = ?");
            $stmt->execute([$target_user_id]);
            $msg = "User blocked successfully.";
        } elseif ($action === 'unblock') {
            $stmt = $pdo->prepare("UPDATE users SET is_blocked = 0 WHERE id = ?");
            $stmt->execute([$target_user_id]);
            $msg = "User unblocked.";
        } elseif ($action === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$target_user_id]);
            $msg = "User account deleted permanently.";
        }
    }
}

// Fetch all users
$search = trim($_GET['search'] ?? '');
$sql = "SELECT * FROM users WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (name LIKE ? OR email LIKE ? OR college LIKE ?)";
    $term = "%{$search}%";
    $params = [$term, $term, $term];
}

$sql .= " ORDER BY id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users_list = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-1">Manage Users</h3>
            <p class="text-muted small mb-0">View all registered student accounts, block violators, or remove users.</p>
        </div>
        <a href="dashboard.php" class="btn btn-outline-secondary rounded-pill"><i class="fas fa-arrow-left me-1"></i> Admin Dashboard</a>
    </div>

    <?php if ($msg): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-1"></i> <?= e($msg) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-1"></i> <?= e($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Search Bar -->
    <div class="keh-card p-3 mb-4">
        <form action="users.php" method="GET" class="row g-2">
            <div class="col-md-9">
                <input type="text" class="form-control" name="search" placeholder="Search by student name, email, or college..." value="<?= e($search) ?>">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100 rounded-pill"><i class="fas fa-search me-1"></i> Search Users</button>
            </div>
        </form>
    </div>

    <!-- Users Table -->
    <div class="keh-card p-4 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>College / Dept</th>
                        <th>Points</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users_list as $u): ?>
                        <tr>
                            <td>#<?= $u['id'] ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <img src="/KEH/uploads/profile_photos/<?= e($u['profile_photo']) ?>" 
                                         onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($u['name']) ?>&background=6366f1&color=fff';"
                                         alt="User" class="rounded-circle object-fit-cover" width="38" height="38">
                                    <div>
                                        <span class="fw-bold d-block"><?= e($u['name']) ?></span>
                                        <span class="text-muted text-xs"><?= e($u['email']) ?></span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="small font-weight-bold d-block"><?= e($u['college'] ?? 'N/A') ?></span>
                                <span class="text-muted text-xs"><?= e($u['department'] ?? '') ?></span>
                            </td>
                            <td class="fw-bold text-warning"><?= (int)$u['points'] ?> pts</td>
                            <td>
                                <?= $u['is_admin'] ? '<span class="badge bg-purple rounded-pill">Admin</span>' : '<span class="badge bg-light text-dark border">Student</span>' ?>
                            </td>
                            <td>
                                <?= $u['is_blocked'] ? '<span class="badge bg-danger rounded-pill">Blocked</span>' : '<span class="badge bg-success rounded-pill">Active</span>' ?>
                            </td>
                            <td class="text-end">
                                <?php if ($u['id'] != $current_user['id']): ?>
                                    <?php if ($u['is_blocked']): ?>
                                        <a href="users.php?action=unblock&id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-success rounded-pill me-1">Unblock</a>
                                    <?php else: ?>
                                        <a href="users.php?action=block&id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-warning rounded-pill me-1" onclick="return confirm('Block this user account?');">Block</a>
                                    <?php endif; ?>
                                    <a href="users.php?action=delete&id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-danger rounded-circle" onclick="return confirm('Permanently delete this user account?');" title="Delete">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted text-xs">Your Account</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
