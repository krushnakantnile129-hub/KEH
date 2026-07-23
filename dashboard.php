<?php
$page_title = "Dashboard";
require_once __DIR__ . '/includes/auth-check.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

$user_id = $current_user['id'];

// Get user rating
$rating_info = get_user_rating($pdo, $user_id);

// Get upcoming sessions
$stmt = $pdo->prepare("
    SELECT s.*, 
           u_learner.name as learner_name, u_learner.profile_photo as learner_photo,
           u_mentor.name as mentor_name, u_mentor.profile_photo as mentor_photo
    FROM sessions s
    JOIN users u_learner ON s.learner_id = u_learner.id
    JOIN users u_mentor ON s.mentor_id = u_mentor.id
    WHERE (s.learner_id = ? OR s.mentor_id = ?) AND s.status IN ('scheduled', 'ongoing')
    ORDER BY s.session_date ASC, s.session_time ASC
");
$stmt->execute([$user_id, $user_id]);
$upcoming_sessions = $stmt->fetchAll();

// Get active accepted learning requests
$stmt = $pdo->prepare("
    SELECT lr.*, s.skill_name,
           u_other.id as partner_id, u_other.name as partner_name, u_other.profile_photo as partner_photo
    FROM learning_requests lr
    JOIN skills s ON lr.skill_id = s.id
    JOIN users u_other ON (CASE WHEN lr.learner_id = ? THEN lr.mentor_id ELSE lr.learner_id END) = u_other.id
    WHERE (lr.learner_id = ? OR lr.mentor_id = ?) AND lr.status = 'accepted'
    ORDER BY lr.created_at DESC
");
$stmt->execute([$user_id, $user_id, $user_id]);
$active_chats = $stmt->fetchAll();

// Get user skills
$stmt = $pdo->prepare("
    SELECT us.*, s.skill_name 
    FROM user_skills us 
    JOIN skills s ON us.skill_id = s.id 
    WHERE us.user_id = ?
");
$stmt->execute([$user_id]);
$all_user_skills = $stmt->fetchAll();

$teach_skills = array_filter($all_user_skills, fn($s) => $s['skill_type'] === 'teach');
$learn_skills = array_filter($all_user_skills, fn($s) => $s['skill_type'] === 'learn');

// Get total completed sessions
$completed_count = $pdo->prepare("SELECT COUNT(*) FROM sessions WHERE (learner_id = ? OR mentor_id = ?) AND status = 'completed'");
$completed_count->execute([$user_id, $user_id]);
$total_completed = $completed_count->fetchColumn();

// Get leaderboard rank
$rank_stmt = $pdo->query("SELECT id FROM users WHERE is_admin = 0 AND is_blocked = 0 ORDER BY points DESC");
$all_ranks = $rank_stmt->fetchAll(PDO::FETCH_COLUMN);
$my_rank = array_search($user_id, $all_ranks);
$my_rank = ($my_rank !== false) ? $my_rank + 1 : '-';

$msg = $_GET['msg'] ?? '';
$error = $_GET['error'] ?? '';
?>

<div class="container py-4">
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

    <!-- Welcome Header Card -->
    <div class="keh-card p-4 mb-4 bg-white border-0 shadow-sm rounded-4">
        <div class="row align-items-center">
            <div class="col-md-8 d-flex align-items-center gap-3">
                <img src="/KEH/uploads/profile_photos/<?= e($current_user['profile_photo']) ?>" 
                     onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($current_user['name']) ?>&background=6366f1&color=fff';"
                     alt="Profile" class="avatar-lg rounded-circle object-fit-cover shadow-sm" width="70" height="70">
                <div>
                    <h3 class="fw-bold mb-1">Hello, <?= e($current_user['name']) ?> 👋</h3>
                    <p class="text-muted mb-0">
                        <i class="fas fa-university text-primary me-1"></i> <?= e($current_user['college'] ?? 'Student') ?> 
                        <span class="mx-1">•</span> <?= e($current_user['department'] ?? 'Department') ?>
                    </p>
                </div>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <?php $my_comp_pct = get_profile_completion_percentage($pdo, $current_user); ?>
                <div class="mt-3 pt-3 border-top">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="small fw-semibold text-muted"><i class="fas fa-tasks text-purple me-1"></i> Profile Completion Status: <strong class="text-dark"><?= $my_comp_pct ?>% Completed</strong></span>
                        <?php if ($my_comp_pct < 100): ?>
                            <a href="edit-profile.php" class="small text-primary text-decoration-none fw-bold">Complete Profile &rarr;</a>
                        <?php else: ?>
                            <span class="badge bg-success-subtle text-success-emphasis rounded-pill"><i class="fas fa-check-circle me-1"></i> 100% Complete</span>
                        <?php endif; ?>
                    </div>
                    <div class="progress" style="height: 8px; background-color: #e2e8f0;">
                        <div class="progress-bar bg-purple" role="progressbar" style="width: <?= $my_comp_pct ?>%" aria-valuenow="<?= $my_comp_pct ?>" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Metrics Row -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="keh-card p-3 text-center h-100">
                <div class="text-warning mb-1 fs-4"><i class="fas fa-coins"></i></div>
                <h3 class="fw-bold mb-0"><?= (int)$current_user['points'] ?></h3>
                <span class="text-muted small">Total Points</span>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="keh-card p-3 text-center h-100">
                <div class="text-success mb-1 fs-4"><i class="fas fa-calendar-check"></i></div>
                <h3 class="fw-bold mb-0"><?= (int)$total_completed ?></h3>
                <span class="text-muted small">Sessions Taught &amp; Learned</span>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="keh-card p-3 text-center h-100">
                <div class="text-info mb-1 fs-4"><i class="fas fa-star"></i></div>
                <h3 class="fw-bold mb-0"><?= $rating_info['avg'] > 0 ? $rating_info['avg'] : 'N/A' ?></h3>
                <span class="text-muted small">Rating (<?= $rating_info['count'] ?> reviews)</span>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="keh-card p-3 text-center h-100">
                <div class="text-purple mb-1 fs-4"><i class="fas fa-trophy"></i></div>
                <h3 class="fw-bold mb-0">#<?= $my_rank ?></h3>
                <span class="text-muted small">Platform Rank</span>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Upcoming Sessions Column -->
        <div class="col-lg-7">
            <div class="keh-card p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0"><i class="fas fa-video text-primary me-2"></i> Upcoming Learning Sessions</h5>
                    <a href="my-sessions.php" class="small text-primary text-decoration-none fw-semibold">View All</a>
                </div>

                <?php if (empty($upcoming_sessions)): ?>
                    <div class="text-center py-4 bg-light rounded-3">
                        <i class="fas fa-calendar-times text-muted fs-2 mb-2"></i>
                        <p class="text-muted small mb-0">No upcoming sessions scheduled right now.</p>
                        <a href="find-mentor.php" class="btn btn-sm btn-outline-primary mt-2 rounded-pill">Schedule a Session</a>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($upcoming_sessions as $sess): ?>
                            <?php 
                                $is_mentor = ($sess['mentor_id'] == $user_id);
                                $partner_name = $is_mentor ? $sess['learner_name'] : $sess['mentor_name'];
                                $partner_photo = $is_mentor ? $sess['learner_photo'] : $sess['mentor_photo'];
                                $role_label = $is_mentor ? 'Learner' : 'Mentor';
                            ?>
                            <div class="list-group-item px-0 py-3 d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-3">
                                    <img src="/KEH/uploads/profile_photos/<?= e($partner_photo) ?>" 
                                         onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($partner_name) ?>&background=6366f1&color=fff';"
                                         alt="Partner" class="rounded-circle object-fit-cover" width="45" height="45">
                                    <div>
                                        <h6 class="fw-bold mb-1"><?= e($sess['session_topic']) ?></h6>
                                        <div class="small text-muted">
                                            <span><i class="fas fa-user me-1 text-secondary"></i> <?= $role_label ?>: <?= e($partner_name) ?></span>
                                            <span class="mx-1">•</span>
                                            <span><i class="fas fa-clock me-1 text-primary"></i> <?= date('M j', strtotime($sess['session_date'])) ?> at <?= date('g:i A', strtotime($sess['session_time'])) ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <a href="video-call.php?room=<?= urlencode($sess['room_id']) ?>" class="btn btn-success btn-sm rounded-pill px-3 shadow-xs">
                                        <i class="fas fa-video me-1"></i> Join Call
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Skills & Quick Messaging Column -->
        <div class="col-lg-5">
            <!-- Active Connections & Chat -->
            <div class="keh-card p-4 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0"><i class="fas fa-comments text-purple me-2"></i> Active Chats</h5>
                    <a href="chat.php" class="small text-purple text-decoration-none fw-semibold">Open Chat</a>
                </div>

                <?php if (empty($active_chats)): ?>
                    <p class="text-muted small mb-0">No active request chats yet. Accept a request to unlock chat.</p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach (array_slice($active_chats, 0, 3) as $chat): ?>
                            <a href="chat.php?request_id=<?= $chat['id'] ?>" class="list-group-item list-group-item-action px-0 py-2 d-flex align-items-center gap-3 border-0">
                                <img src="/KEH/uploads/profile_photos/<?= e($chat['partner_photo']) ?>" 
                                     onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($chat['partner_name']) ?>&background=6366f1&color=fff';"
                                     alt="Partner" class="rounded-circle object-fit-cover" width="40" height="40">
                                <div class="flex-grow-1">
                                    <h6 class="fw-semibold mb-0 small"><?= e($chat['partner_name']) ?></h6>
                                    <span class="text-muted small"><?= e($chat['skill_name']) ?></span>
                                </div>
                                <span class="btn btn-sm btn-light rounded-circle"><i class="fas fa-chevron-right text-muted small"></i></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- My Skills Breakdown -->
            <div class="keh-card p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0"><i class="fas fa-tools text-warning me-2"></i> My Skills</h5>
                    <a href="add-skills.php" class="small text-warning-emphasis text-decoration-none fw-semibold">Edit Skills</a>
                </div>

                <div class="mb-3">
                    <label class="form-label small text-muted fw-bold">Skills I Can Teach:</label>
                    <div>
                        <?php if (empty($teach_skills)): ?>
                            <span class="text-muted small fst-italic">No teaching skills added yet.</span>
                        <?php else: ?>
                            <?php foreach ($teach_skills as $ts): ?>
                                <span class="skill-chip badge-prof-advanced mb-1 me-1"><?= e($ts['skill_name']) ?> (<?= $ts['proficiency_level'] ?>)</span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div>
                    <label class="form-label small text-muted fw-bold">Skills I Want to Learn:</label>
                    <div>
                        <?php if (empty($learn_skills)): ?>
                            <span class="text-muted small fst-italic">No learning goals added yet.</span>
                        <?php else: ?>
                            <?php foreach ($learn_skills as $ls): ?>
                                <span class="skill-chip badge-prof-beginner mb-1 me-1"><?= e($ls['skill_name']) ?> (<?= $ls['proficiency_level'] ?>)</span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
