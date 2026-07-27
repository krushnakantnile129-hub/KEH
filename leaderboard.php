<?php
$page_title = "Student Leaderboard";
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

// Fetch users ordered by total points
$stmt = $pdo->query("
    SELECT u.*,
           (SELECT COUNT(id) FROM sessions WHERE (learner_id = u.id OR mentor_id = u.id) AND status = 'completed') as completed_sessions,
           (SELECT AVG(rating) FROM reviews WHERE reviewed_user_id = u.id) as avg_rating
    FROM users u
    WHERE u.is_blocked = 0
    ORDER BY u.points DESC, completed_sessions DESC
");
$rankings = $stmt->fetchAll();
?>
<div class="container py-4">
    <!-- Leaderboard Header Card -->
    <div class="keh-card p-4 p-md-5 mb-4 shadow-sm text-center">
        <div class="brand-icon mx-auto mb-2 bg-warning text-dark fs-3" style="width: 56px; height: 56px;">
            <i class="fas fa-trophy"></i>
        </div>
        <h2 class="fw-bold mb-2">Student &amp; Top Tutors</h2>
        <p class="text-muted small mb-0">Rankings based on teaching sessions, learning completions, and 5-star student reviews.</p>
    </div>

    <!-- Points System Rule Banner -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="keh-card p-3 text-center bg-white">
                <span class="badge bg-success mb-1">+10 Points</span>
                <span class="d-block small text-muted font-weight-bold">Teaching a Session</span>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="keh-card p-3 text-center bg-white">
                <span class="badge bg-info mb-1">+5 Points</span>
                <span class="d-block small text-muted font-weight-bold">Completing a Session</span>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="keh-card p-3 text-center bg-white">
                <span class="badge bg-warning text-dark mb-1">+3 Points</span>
                <span class="d-block small text-muted font-weight-bold">5-Star Review</span>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="keh-card p-3 text-center bg-white">
                <span class="badge bg-purple mb-1">+5 Points</span>
                <span class="d-block small text-muted font-weight-bold">Profile Completion</span>
            </div>
        </div>
    </div>

    <!-- Leaderboard Table Card -->
    <div class="keh-card p-4 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 80px;">Rank</th>
                        <th>Student</th>
                        <th>College / Major</th>
                        <th>Profile Completion</th>
                        <th>Sessions</th>
                        <th>Rating</th>
                        <th class="text-end">Total Points</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $rank = 1; ?>
                    <?php foreach ($rankings as $user): ?>
                        <?php 
                            $rating = $user['avg_rating'] ? round((float)$user['avg_rating'], 1) : 0.0;
                            $comp_pct = get_profile_completion_percentage($pdo, $user);
                            $progress_color = 'bg-success';
                            if ($comp_pct < 60) $progress_color = 'bg-info';
                            if ($comp_pct < 40) $progress_color = 'bg-warning';

                            $rank_class = '';
                            if ($rank === 1) $rank_class = 'rank-1';
                            elseif ($rank === 2) $rank_class = 'rank-2';
                            elseif ($rank === 3) $rank_class = 'rank-3';
                        ?>
                        <tr>
                            <td>
                                <span class="rank-badge <?= $rank_class ?>"><?= $rank ?></span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <img src="/KEH/uploads/profile_photos/<?= e($user['profile_photo']) ?>" 
                                         onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($user['name']) ?>&background=6366f1&color=fff';"
                                         alt="<?= e($user['name']) ?>" class="rounded-circle object-fit-cover shadow-xs" width="45" height="45">
                                    <div>
                                        <a href="profile.php?id=<?= $user['id'] ?>" class="fw-bold text-dark text-decoration-none d-block">
                                            <?= e($user['name']) ?>
                                        </a>
                                        <span class="text-muted text-xs"><?= e($user['email']) ?></span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="fw-semibold small d-block"><?= e(!empty($user['college']) ? $user['college'] : 'College Student') ?></span>
                                <span class="text-muted text-xs"><?= e(!empty($user['department']) ? $user['department'] : 'General') ?></span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2" style="max-width: 140px;" title="<?= $comp_pct ?>% Profile Completed">
                                    <div class="progress flex-grow-1" style="height: 7px; background-color: #e2e8f0;">
                                        <div class="progress-bar <?= $progress_color ?>" role="progressbar" style="width: <?= $comp_pct ?>%" aria-valuenow="<?= $comp_pct ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <span class="small fw-bold text-dark text-xs"><?= $comp_pct ?>%</span>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border"><?= (int)$user['completed_sessions'] ?> Sessions</span>
                            </td>
                            <td>
                                <div class="small">
                                    <?= render_stars($rating) ?>
                                    <span class="fw-bold ms-1"><?= $rating > 0 ? $rating : 'N/A' ?></span>
                                </div>
                            </td>
                            <td class="text-end">
                                <span class="points-pill bg-warning-subtle text-warning-emphasis fw-bold px-3 py-1.5 rounded-pill d-inline-block">
                                    <i class="fas fa-coins text-warning me-1"></i> <?= (int)$user['points'] ?> pts
                                </span>
                            </td>
                        </tr>
                        <?php $rank++; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
