<?php
$page_title = "Admin - User Reports";
require_once __DIR__ . '/../includes/admin-check.php';
require_once __DIR__ . '/../includes/functions.php';

$msg = $_GET['msg'] ?? '';
$error = $_GET['error'] ?? '';

// Handle Report Status Update or Block Action
if (isset($_GET['action'])) {
    $report_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    $action    = $_GET['action'];

    if ($report_id) {
        $stmt = $pdo->prepare("SELECT * FROM reports WHERE id = ?");
        $stmt->execute([$report_id]);
        $rep = $stmt->fetch();

        if ($rep) {
            if ($action === 'dismiss') {
                $stmt = $pdo->prepare("UPDATE reports SET status = 'dismissed' WHERE id = ?");
                $stmt->execute([$report_id]);
                $msg = "Report dismissed.";
            } elseif ($action === 'block') {
                $stmt = $pdo->prepare("UPDATE users SET is_blocked = 1 WHERE id = ?");
                $stmt->execute([$rep['reported_user']]);
                
                $stmt = $pdo->prepare("UPDATE reports SET status = 'reviewed' WHERE id = ?");
                $stmt->execute([$report_id]);
                $msg = "Reported user account suspended.";
            }
        }
    }
}

// Fetch all reports
$reports = $pdo->query("
    SELECT r.*,
           u_reporter.name as reporter_name, u_reporter.email as reporter_email,
           u_target.name as target_name, u_target.email as target_email, u_target.is_blocked as target_blocked
    FROM reports r
    JOIN users u_reporter ON r.reported_by = u_reporter.id
    JOIN users u_target ON r.reported_user = u_target.id
    ORDER BY r.created_at DESC
")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-1">Reported Users &amp; Content Moderation</h3>
            <p class="text-muted small mb-0">Review reports filed by students regarding inappropriate conduct or spam.</p>
        </div>
        <a href="dashboard.php" class="btn btn-outline-secondary rounded-pill"><i class="fas fa-arrow-left me-1"></i> Admin Dashboard</a>
    </div>

    <?php if ($msg): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-1"></i> <?= e($msg) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="keh-card p-4 shadow-sm">
        <?php if (empty($reports)): ?>
            <div class="text-center py-5">
                <i class="fas fa-shield-alt fa-3x text-success mb-3"></i>
                <h5>No Reports Found</h5>
                <p class="text-muted small">The platform is clean! No user flags or reports filed.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Reported User</th>
                            <th>Filed By</th>
                            <th>Reason</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reports as $rep): ?>
                            <tr>
                                <td>
                                    <span class="fw-bold d-block text-dark"><?= e($rep['target_name']) ?></span>
                                    <span class="text-muted text-xs"><?= e($rep['target_email']) ?></span>
                                </td>
                                <td>
                                    <span class="fw-semibold small d-block"><?= e($rep['reporter_name']) ?></span>
                                </td>
                                <td>
                                    <p class="text-secondary small mb-0">"<?= e($rep['reason']) ?>"</p>
                                </td>
                                <td>
                                    <?php 
                                        $badge = 'bg-warning';
                                        if ($rep['status'] === 'reviewed') $badge = 'bg-success';
                                        if ($rep['status'] === 'dismissed') $badge = 'bg-secondary';
                                    ?>
                                    <span class="badge <?= $badge ?> rounded-pill text-capitalize"><?= e($rep['status']) ?></span>
                                </td>
                                <td class="small text-muted"><?= format_time($rep['created_at']) ?></td>
                                <td class="text-end">
                                    <?php if ($rep['status'] === 'pending'): ?>
                                        <a href="reports.php?action=block&id=<?= $rep['id'] ?>" class="btn btn-sm btn-danger rounded-pill px-3 me-1" onclick="return confirm('Block this reported user account?');">
                                            <i class="fas fa-user-slash me-1"></i> Block User
                                        </a>
                                        <a href="reports.php?action=dismiss&id=<?= $rep['id'] ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                                            Dismiss
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted text-xs">Processed</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
