<?php
$page_title = "Admin Dashboard";
require_once __DIR__ . '/../includes/admin-check.php';
require_once __DIR__ . '/../includes/functions.php';

// Fetch platform metrics
$total_users = $pdo->query("SELECT COUNT(*) FROM users WHERE is_admin = 0")->fetchColumn();
$active_users = $pdo->query("SELECT COUNT(*) FROM users WHERE is_admin = 0 AND is_blocked = 0")->fetchColumn();
$total_skills = $pdo->query("SELECT COUNT(*) FROM skills")->fetchColumn();
$total_sessions = $pdo->query("SELECT COUNT(*) FROM sessions")->fetchColumn();
$completed_sessions = $pdo->query("SELECT COUNT(*) FROM sessions WHERE status = 'completed'")->fetchColumn();
$pending_reports = $pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'pending'")->fetchColumn();

// Recent registered users
$recent_users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container py-4">
    <!-- Admin Header -->
    <div class="keh-card p-4 mb-4 bg-dark text-white shadow-sm rounded-4">
        <div class="d-flex align-items-center justify-content-between">
            <div>
                <span class="badge bg-danger rounded-pill px-3 py-1 mb-2"><i class="fas fa-user-shield me-1"></i> Admin Panel</span>
                <h2 class="fw-bold text-white mb-0">Platform Overview &amp; Control</h2>
            </div>
            <div>
                <a href="../dashboard.php" class="btn btn-outline-light rounded-pill"><i class="fas fa-user me-1"></i> Student View</a>
            </div>
        </div>
    </div>

    <!-- Statistics Cards Grid -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4 col-lg-2">
            <div class="keh-card p-3 text-center h-100">
                <div class="text-primary mb-1 fs-4"><i class="fas fa-users"></i></div>
                <h3 class="fw-bold mb-0"><?= (int)$total_users ?></h3>
                <span class="text-muted small">Total Users</span>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="keh-card p-3 text-center h-100">
                <div class="text-success mb-1 fs-4"><i class="fas fa-user-check"></i></div>
                <h3 class="fw-bold mb-0"><?= (int)$active_users ?></h3>
                <span class="text-muted small">Active Users</span>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="keh-card p-3 text-center h-100">
                <div class="text-warning mb-1 fs-4"><i class="fas fa-tools"></i></div>
                <h3 class="fw-bold mb-0"><?= (int)$total_skills ?></h3>
                <span class="text-muted small">Total Skills</span>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="keh-card p-3 text-center h-100">
                <div class="text-info mb-1 fs-4"><i class="fas fa-calendar-alt"></i></div>
                <h3 class="fw-bold mb-0"><?= (int)$total_sessions ?></h3>
                <span class="text-muted small">Total Sessions</span>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="keh-card p-3 text-center h-100">
                <div class="text-purple mb-1 fs-4"><i class="fas fa-check-circle"></i></div>
                <h3 class="fw-bold mb-0"><?= (int)$completed_sessions ?></h3>
                <span class="text-muted small">Completed</span>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="keh-card p-3 text-center h-100">
                <div class="text-danger mb-1 fs-4"><i class="fas fa-flag"></i></div>
                <h3 class="fw-bold mb-0"><?= (int)$pending_reports ?></h3>
                <span class="text-muted small">Reports</span>
            </div>
        </div>
    </div>

    <!-- Quick Navigation Links -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <a href="users.php" class="text-decoration-none">
                <div class="keh-card p-4 text-center hover-border-primary">
                    <i class="fas fa-users-cog fa-2x text-primary mb-2"></i>
                    <h5 class="fw-bold text-dark mb-1">Manage Users</h5>
                    <span class="text-muted small">Block, unblock, or delete accounts</span>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="skills.php" class="text-decoration-none">
                <div class="keh-card p-4 text-center hover-border-primary">
                    <i class="fas fa-tools fa-2x text-warning mb-2"></i>
                    <h5 class="fw-bold text-dark mb-1">Manage Skills</h5>
                    <span class="text-muted small">Add or delete catalog skills</span>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="categories.php" class="text-decoration-none">
                <div class="keh-card p-4 text-center hover-border-primary">
                    <i class="fas fa-layer-group fa-2x text-info mb-2"></i>
                    <h5 class="fw-bold text-dark mb-1">Manage Categories</h5>
                    <span class="text-muted small">Add or delete skill categories</span>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="reports.php" class="text-decoration-none">
                <div class="keh-card p-4 text-center hover-border-primary">
                    <i class="fas fa-flag fa-2x text-danger mb-2"></i>
                    <h5 class="fw-bold text-dark mb-1">User Reports</h5>
                    <span class="text-muted small">Review reported user accounts</span>
                </div>
            </a>
        </div>
    </div>

    <!-- Recent Users Table -->
    <div class="keh-card p-4 shadow-sm">
        <h5 class="fw-bold mb-3"><i class="fas fa-clock text-primary me-2"></i> Recently Registered Users</h5>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th>College / Major</th>
                        <th>Status</th>
                        <th>Joined</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_users as $u): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <img src="/KEH/uploads/profile_photos/<?= e($u['profile_photo']) ?>" 
                                         onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($u['name']) ?>&background=6366f1&color=fff';"
                                         alt="User" class="rounded-circle object-fit-cover" width="35" height="35">
                                    <span class="fw-bold"><?= e($u['name']) ?></span>
                                </div>
                            </td>
                            <td class="small text-muted"><?= e($u['email']) ?></td>
                            <td class="small"><?= e($u['college']) ?> (<?= e($u['department']) ?>)</td>
                            <td>
                                <?php if ($u['is_blocked']): ?>
                                    <span class="badge bg-danger rounded-pill">Blocked</span>
                                <?php elseif ($u['is_admin']): ?>
                                    <span class="badge bg-purple rounded-pill">Admin</span>
                                <?php else: ?>
                                    <span class="badge bg-success rounded-pill">Active</span>
                                <?php endif; ?>
                            </td>
                            <td class="small text-muted"><?= format_time($u['created_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
